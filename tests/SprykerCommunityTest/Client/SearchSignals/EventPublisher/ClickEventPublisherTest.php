<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchSignals\EventPublisher;

use Codeception\Test\Unit;
use SprykerCommunity\Client\SearchSignals\Dependency\Client\SearchSignalsToStorageClientInterface;
use SprykerCommunity\Client\SearchSignals\EventPublisher\ClickEventPublisher;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig;

/**
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchSignals
 * @group EventPublisher
 * @group ClickEventPublisherTest
 * @group Portable
 */
class ClickEventPublisherTest extends Unit
{
    public function testPublishWritesAJsonEncodedPayloadUnderAUniqueClickPrefixedKey(): void
    {
        // Arrange
        $storageClientMock = $this->getMockBuilder(SearchSignalsToStorageClientInterface::class)->getMock();
        $storageClientMock->expects($this->once())->method('set')
            ->with(
                $this->callback(fn (string $key): bool => str_starts_with($key, SearchSignalsConfig::STORAGE_KEY_PREFIX_CLICK_EVENT)),
                $this->callback(function (string $value): bool {
                    $decoded = json_decode($value, true);

                    return $decoded === [
                        'query' => 'office chair',
                        'abstractSku' => 'ABC-123',
                        'rank' => 2,
                        'storeName' => 'DE',
                        'localeName' => 'de_DE',
                    ];
                }),
            );

        // Act
        (new ClickEventPublisher($storageClientMock))->publish('office chair', 'ABC-123', 2, 'DE', 'de_DE');
    }

    public function testPublishGeneratesADistinctKeyOnEachCall(): void
    {
        // Arrange
        $seenKeys = [];
        $storageClientMock = $this->getMockBuilder(SearchSignalsToStorageClientInterface::class)->getMock();
        $storageClientMock->method('set')->willReturnCallback(function (string $key) use (&$seenKeys): void {
            $seenKeys[] = $key;
        });
        $publisher = new ClickEventPublisher($storageClientMock);

        // Act
        $publisher->publish('chair', 'ABC-123', 0, 'DE', 'de_DE');
        $publisher->publish('chair', 'ABC-123', 0, 'DE', 'de_DE');

        // Assert
        $this->assertNotSame($seenKeys[0], $seenKeys[1]);
    }
}
