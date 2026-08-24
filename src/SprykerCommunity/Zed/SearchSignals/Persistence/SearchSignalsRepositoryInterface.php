<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Persistence;

use DateTimeInterface;

interface SearchSignalsRepositoryInterface
{
    /**
     * @param \DateTimeInterface $since
     *
     * @return array<int, array{query: string, abstractSku: string, rank: int, storeName: string, localeName: string, capturedAt: \DateTimeInterface}>
     */
    public function getImpressionEventsCapturedSince(DateTimeInterface $since): array;

    /**
     * @param \DateTimeInterface $since
     *
     * @return array<int, array{query: string, abstractSku: string, rank: int, storeName: string, localeName: string, capturedAt: \DateTimeInterface}>
     */
    public function getClickEventsCapturedSince(DateTimeInterface $since): array;

    /**
     * Summed rollup counts per product, over the given flat time window -- the P4 emit read. Includes the
     * unattributed cart-add/order counts (channel 3b's "small version") alongside impressions/clicks,
     * since they live in the same daily bucket row.
     *
     * @param string $storeName
     * @param string $localeName
     * @param \DateTimeInterface $since
     *
     * @return array<string, array{impressionCount: int, clickCount: int, cartAddCount: int, orderCount: int}> Keyed by abstract sku.
     */
    public function getProductCtrTotals(string $storeName, string $localeName, DateTimeInterface $since): array;

    /**
     * Summed rollup counts per (query, product), over the given flat time window -- the P5 query-weight
     * export read (all-time cumulative in practice, see the search-signals plan; `$since` still bounds
     * the query for callers that want a shorter window).
     *
     * @param string $storeName
     * @param string $localeName
     * @param \DateTimeInterface $since
     *
     * @return array<string, array{impressionCount: int, clickCount: int}> Keyed by "query|abstractSku".
     */
    public function getQueryCtrTotals(string $storeName, string $localeName, DateTimeInterface $since): array;

    /**
     * Total impression volume per query, summed across every product it returned -- what channel 3a's
     * query-weight export actually needs (see the search-signals plan's P5 floor/blend formula).
     *
     * @param string $storeName
     * @param string $localeName
     *
     * @return array<string, int> Keyed by query.
     */
    public function getQueryImpressionVolumes(string $storeName, string $localeName): array;
}
