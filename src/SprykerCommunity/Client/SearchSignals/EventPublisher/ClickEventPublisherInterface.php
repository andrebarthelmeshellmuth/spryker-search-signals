<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals\EventPublisher;

interface ClickEventPublisherInterface
{
    /**
     * @param string $query
     * @param string $abstractSku
     * @param int $rank
     * @param string $storeName
     * @param string $localeName
     */
    public function publish(string $query, string $abstractSku, int $rank, string $storeName, string $localeName): void;
}
