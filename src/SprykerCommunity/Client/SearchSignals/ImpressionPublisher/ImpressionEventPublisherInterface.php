<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals\ImpressionPublisher;

interface ImpressionEventPublisherInterface
{
    /**
     * Publishes one impression event per result to the queue -- never a direct DB write from the
     * request path, see the search-signals plan's P1 decision.
     *
     * @param string $query
     * @param array<int, string> $abstractSkusByRank Zero-indexed rank => abstract sku.
     * @param string $storeName
     * @param string $localeName
     */
    public function publish(string $query, array $abstractSkusByRank, string $storeName, string $localeName): void;
}
