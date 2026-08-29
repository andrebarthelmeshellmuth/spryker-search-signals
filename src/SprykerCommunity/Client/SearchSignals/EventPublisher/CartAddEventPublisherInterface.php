<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals\EventPublisher;

interface CartAddEventPublisherInterface
{
    /**
     * Deliberately unattributed to any search term (channel 3b's "small version") -- just
     * (sku, store, locale), no query/rank.
     *
     * @param string $abstractSku
     * @param string $storeName
     * @param string $localeName
     */
    public function publish(string $abstractSku, string $storeName, string $localeName): void;
}
