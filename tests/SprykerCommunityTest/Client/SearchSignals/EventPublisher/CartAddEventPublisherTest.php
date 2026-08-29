<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchSignals\EventPublisher;

use Codeception\Test\Unit;
use SprykerCommunity\Client\SearchSignals\Dependency\Client\SearchSignalsToStorageClientInterface;
use SprykerCommunity\Client\SearchSignals\EventPublisher\CartAddEventPublisher;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig;

/**
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchSignals
 * @group EventPublisher
 * @group CartAddEventPublisherTest
 * @group Portable
 */
class CartAddEventPublisherTest extends Unit
{
    public function testPublishWritesAJsonEncodedPayloadUnderAUniqueCartAddPrefixedKey(): void
    {
        // Arrange
        $storageClientMock = $this->getMockBuilder(SearchSignalsToStorageClientInterface::class)->getMock();
        $storageClientMock->expects($this->once())->method('set')
            ->with(
                $this->callback(fn (string $key): bool => str_starts_with($key, SearchSignalsConfig::STORAGE_KEY_PREFIX_CART_ADD_EVENT)),
                $this->callback(function (string $value): bool {
                    $decoded = json_decode($value, true);

                    return $decoded === [
                        'abstractSku' => 'ABC-123',
                        'storeName' => 'DE',
                        'localeName' => 'de_DE',
                    ];
                }),
            );

        // Act
        (new CartAddEventPublisher($storageClientMock))->publish('ABC-123', 'DE', 'de_DE');
    }
}
