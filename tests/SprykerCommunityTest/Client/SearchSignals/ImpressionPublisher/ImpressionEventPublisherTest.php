<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchSignals\ImpressionPublisher;

use Codeception\Test\Unit;
use SprykerCommunity\Client\SearchSignals\Dependency\Client\SearchSignalsToStorageClientInterface;
use SprykerCommunity\Client\SearchSignals\ImpressionPublisher\ImpressionEventPublisher;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig;

/**
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchSignals
 * @group ImpressionPublisher
 * @group ImpressionEventPublisherTest
 * @group Portable
 */
class ImpressionEventPublisherTest extends Unit
{
    public function testPublishWritesOneKeyPerRankedProductWithItsRankPreserved(): void
    {
        // Arrange
        $writtenPayloads = [];
        $storageClientMock = $this->getMockBuilder(SearchSignalsToStorageClientInterface::class)->getMock();
        $storageClientMock->expects($this->exactly(3))->method('set')
            ->willReturnCallback(function (string $key, string $value) use (&$writtenPayloads): void {
                $this->assertStringStartsWith(SearchSignalsConfig::STORAGE_KEY_PREFIX_IMPRESSION_EVENT, $key);
                $writtenPayloads[] = json_decode($value, true);
            });

        // Act
        (new ImpressionEventPublisher($storageClientMock))->publish(
            'office chair',
            [0 => 'ABC-123', 1 => 'DEF-456', 2 => 'GHI-789'],
            'DE',
            'de_DE',
        );

        // Assert
        $this->assertSame(0, $writtenPayloads[0]['rank']);
        $this->assertSame('ABC-123', $writtenPayloads[0]['abstractSku']);
        $this->assertSame(1, $writtenPayloads[1]['rank']);
        $this->assertSame('DEF-456', $writtenPayloads[1]['abstractSku']);
        $this->assertSame(2, $writtenPayloads[2]['rank']);
        $this->assertSame('GHI-789', $writtenPayloads[2]['abstractSku']);
    }

    public function testPublishWritesNothingForAnEmptyResultSet(): void
    {
        // Arrange
        $storageClientMock = $this->getMockBuilder(SearchSignalsToStorageClientInterface::class)->getMock();
        $storageClientMock->expects($this->never())->method('set');

        // Act
        (new ImpressionEventPublisher($storageClientMock))->publish('office chair', [], 'DE', 'de_DE');
    }
}
