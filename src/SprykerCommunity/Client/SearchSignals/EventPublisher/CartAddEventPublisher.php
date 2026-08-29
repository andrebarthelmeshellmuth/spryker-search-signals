<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals\EventPublisher;

use SprykerCommunity\Client\SearchSignals\Dependency\Client\SearchSignalsToStorageClientInterface;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig;

class CartAddEventPublisher implements CartAddEventPublisherInterface
{
    /**
     * @param \SprykerCommunity\Client\SearchSignals\Dependency\Client\SearchSignalsToStorageClientInterface $storageClient
     */
    public function __construct(protected SearchSignalsToStorageClientInterface $storageClient)
    {
    }

    public function publish(string $abstractSku, string $storeName, string $localeName): void
    {
        $key = SearchSignalsConfig::STORAGE_KEY_PREFIX_CART_ADD_EVENT . uniqid('', true);

        $this->storageClient->set($key, json_encode([
            'abstractSku' => $abstractSku,
            'storeName' => $storeName,
            'localeName' => $localeName,
        ], JSON_THROW_ON_ERROR));
    }
}
