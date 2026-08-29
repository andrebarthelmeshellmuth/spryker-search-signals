<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignals\Business\Drain;

use Codeception\Test\Unit;
use DateTimeInterface;
use Generated\Shared\Transfer\StorageScanResultTransfer;
use RuntimeException;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig;
use SprykerCommunity\Zed\SearchSignals\Business\Capture\ClickEventWriterInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Capture\ImpressionEventWriterInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Drain\QueueDrainer;
use SprykerCommunity\Zed\SearchSignals\Dependency\Client\SearchSignalsToStorageClientInterface;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface;

/**
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchSignals
 * @group Business
 * @group Drain
 * @group QueueDrainerTest
 * @group Portable
 */
class QueueDrainerTest extends Unit
{
    public function testDrainReturnsAllZeroesAndNeverCallsGetMultiOrDeleteMultiWhenTheQueueIsEmpty(): void
    {
        // Arrange
        $storageClientMock = $this->getMockBuilder(SearchSignalsToStorageClientInterface::class)->getMock();
        $storageClientMock->method('scanKeys')->willReturn($this->scanResult([], 0));
        $storageClientMock->expects($this->never())->method('getMulti');
        $storageClientMock->expects($this->never())->method('deleteMulti');

        $drainer = $this->createDrainer($storageClientMock);

        // Act & Assert
        $this->assertSame(['impressionsDrained' => 0, 'clicksDrained' => 0, 'cartAddsDrained' => 0], $drainer->drain());
    }

    public function testDrainWritesAWellFormedImpressionEventAndDeletesItsKey(): void
    {
        // Arrange
        $key = SearchSignalsConfig::STORAGE_KEY_PREFIX_IMPRESSION_EVENT . 'evt1';
        $payload = json_encode(['query' => 'chair', 'abstractSku' => 'ABC-123', 'rank' => 2, 'storeName' => 'DE', 'localeName' => 'de_DE']);

        $storageClientMock = $this->getMockBuilder(SearchSignalsToStorageClientInterface::class)->getMock();
        $storageClientMock->method('scanKeys')->willReturnCallback(
            fn (string $pattern): StorageScanResultTransfer => str_starts_with($pattern, SearchSignalsConfig::STORAGE_KEY_PREFIX_IMPRESSION_EVENT)
                ? $this->scanResult(['kv:' . $key], 0)
                : $this->scanResult([], 0),
        );
        $storageClientMock->method('getMulti')->with([$key])->willReturn([$key => $payload]);
        $storageClientMock->expects($this->once())->method('deleteMulti')->with([$key]);

        $impressionWriterMock = $this->getMockBuilder(ImpressionEventWriterInterface::class)->getMock();
        $impressionWriterMock->expects($this->once())->method('write')->with('chair', 'ABC-123', 2, 'DE', 'de_DE');

        $drainer = $this->createDrainer($storageClientMock, impressionEventWriter: $impressionWriterMock);

        // Act & Assert
        $this->assertSame(['impressionsDrained' => 1, 'clicksDrained' => 0, 'cartAddsDrained' => 0], $drainer->drain());
    }

    public function testDrainWritesACartAddEventDirectlyToTheEntityManagerNotTheCaptureWriters(): void
    {
        // Arrange
        $key = SearchSignalsConfig::STORAGE_KEY_PREFIX_CART_ADD_EVENT . 'evt1';
        $payload = json_encode(['abstractSku' => 'ABC-123', 'storeName' => 'DE', 'localeName' => 'de_DE']);

        $storageClientMock = $this->getMockBuilder(SearchSignalsToStorageClientInterface::class)->getMock();
        $storageClientMock->method('scanKeys')->willReturnCallback(
            fn (string $pattern): StorageScanResultTransfer => str_starts_with($pattern, SearchSignalsConfig::STORAGE_KEY_PREFIX_CART_ADD_EVENT)
                ? $this->scanResult(['kv:' . $key], 0)
                : $this->scanResult([], 0),
        );
        $storageClientMock->method('getMulti')->willReturn([$key => $payload]);
        $storageClientMock->expects($this->once())->method('deleteMulti')->with([$key]);

        $entityManagerMock = $this->getMockBuilder(SearchSignalsEntityManagerInterface::class)->getMock();
        $entityManagerMock->expects($this->once())->method('incrementProductCartAddCount')
            ->with('ABC-123', 'DE', 'de_DE', $this->isInstanceOf(DateTimeInterface::class), 1);

        $drainer = $this->createDrainer($storageClientMock, entityManager: $entityManagerMock);

        // Act & Assert
        $this->assertSame(['impressionsDrained' => 0, 'clicksDrained' => 0, 'cartAddsDrained' => 1], $drainer->drain());
    }

    public function testDrainDeletesAMalformedJsonKeyWithoutCountingItAsDrained(): void
    {
        // Arrange
        $key = SearchSignalsConfig::STORAGE_KEY_PREFIX_IMPRESSION_EVENT . 'bad';

        $storageClientMock = $this->getMockBuilder(SearchSignalsToStorageClientInterface::class)->getMock();
        $storageClientMock->method('scanKeys')->willReturnCallback(
            fn (string $pattern): StorageScanResultTransfer => str_starts_with($pattern, SearchSignalsConfig::STORAGE_KEY_PREFIX_IMPRESSION_EVENT)
                ? $this->scanResult(['kv:' . $key], 0)
                : $this->scanResult([], 0),
        );
        $storageClientMock->method('getMulti')->willReturn([$key => 'not-valid-json{{{']);
        $storageClientMock->expects($this->once())->method('deleteMulti')->with([$key]);

        $impressionWriterMock = $this->getMockBuilder(ImpressionEventWriterInterface::class)->getMock();
        $impressionWriterMock->expects($this->never())->method('write');

        $drainer = $this->createDrainer($storageClientMock, impressionEventWriter: $impressionWriterMock);

        // Act & Assert
        $this->assertSame(0, $drainer->drain()['impressionsDrained']);
    }

    public function testDrainLeavesAKeyInPlaceWhenTheWriteCallbackThrows(): void
    {
        // Arrange -- a transient DB failure must not lose the event: the key stays for the next drain run.
        $key = SearchSignalsConfig::STORAGE_KEY_PREFIX_CLICK_EVENT . 'evt1';
        $payload = json_encode(['query' => 'chair', 'abstractSku' => 'ABC-123', 'rank' => 2, 'storeName' => 'DE', 'localeName' => 'de_DE']);

        $storageClientMock = $this->getMockBuilder(SearchSignalsToStorageClientInterface::class)->getMock();
        $storageClientMock->method('scanKeys')->willReturnCallback(
            fn (string $pattern): StorageScanResultTransfer => str_starts_with($pattern, SearchSignalsConfig::STORAGE_KEY_PREFIX_CLICK_EVENT)
                ? $this->scanResult(['kv:' . $key], 0)
                : $this->scanResult([], 0),
        );
        $storageClientMock->method('getMulti')->willReturn([$key => $payload]);
        $storageClientMock->expects($this->never())->method('deleteMulti');

        $clickWriterMock = $this->getMockBuilder(ClickEventWriterInterface::class)->getMock();
        $clickWriterMock->method('write')->willThrowException(new RuntimeException('transient DB error'));

        $drainer = $this->createDrainer($storageClientMock, clickEventWriter: $clickWriterMock);

        // Act & Assert
        $this->assertSame(0, $drainer->drain()['clicksDrained']);
    }

    public function testDrainFollowsPaginationAcrossMultipleScanBatchesUntilCursorReturnsToZero(): void
    {
        // Arrange -- two batches for the impression prefix: first returns a non-zero cursor, second stops.
        $key1 = SearchSignalsConfig::STORAGE_KEY_PREFIX_IMPRESSION_EVENT . 'evt1';
        $key2 = SearchSignalsConfig::STORAGE_KEY_PREFIX_IMPRESSION_EVENT . 'evt2';
        $payload = json_encode(['query' => 'chair', 'abstractSku' => 'ABC-123', 'rank' => 0, 'storeName' => 'DE', 'localeName' => 'de_DE']);

        $callCount = 0;
        $storageClientMock = $this->getMockBuilder(SearchSignalsToStorageClientInterface::class)->getMock();
        $storageClientMock->method('scanKeys')->willReturnCallback(function (string $pattern) use (&$callCount, $key1, $key2): StorageScanResultTransfer {
            if (!str_starts_with($pattern, SearchSignalsConfig::STORAGE_KEY_PREFIX_IMPRESSION_EVENT)) {
                return $this->scanResult([], 0);
            }

            $callCount++;

            return $callCount === 1 ? $this->scanResult(['kv:' . $key1], 5) : $this->scanResult(['kv:' . $key2], 0);
        });
        $storageClientMock->method('getMulti')->willReturnCallback(
            fn (array $keys): array => [$keys[0] => $payload],
        );
        $storageClientMock->expects($this->exactly(2))->method('deleteMulti');

        $impressionWriterMock = $this->getMockBuilder(ImpressionEventWriterInterface::class)->getMock();
        $impressionWriterMock->expects($this->exactly(2))->method('write');

        $drainer = $this->createDrainer($storageClientMock, impressionEventWriter: $impressionWriterMock);

        // Act & Assert
        $this->assertSame(2, $drainer->drain()['impressionsDrained']);
    }

    /**
     * @param array<string> $keys
     */
    protected function scanResult(array $keys, int $cursor): StorageScanResultTransfer
    {
        return (new StorageScanResultTransfer())->setKeys($keys)->setCursor($cursor);
    }

    protected function createDrainer(
        SearchSignalsToStorageClientInterface $storageClient,
        ?ImpressionEventWriterInterface $impressionEventWriter = null,
        ?ClickEventWriterInterface $clickEventWriter = null,
        ?SearchSignalsEntityManagerInterface $entityManager = null,
    ): QueueDrainer {
        return new QueueDrainer(
            $storageClient,
            $impressionEventWriter ?? $this->getMockBuilder(ImpressionEventWriterInterface::class)->getMock(),
            $clickEventWriter ?? $this->getMockBuilder(ClickEventWriterInterface::class)->getMock(),
            $entityManager ?? $this->getMockBuilder(SearchSignalsEntityManagerInterface::class)->getMock(),
        );
    }
}
