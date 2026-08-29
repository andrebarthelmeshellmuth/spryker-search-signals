<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business;

use DateTimeInterface;

interface SearchSignalsFacadeInterface
{
    /**
     * @api
     *
     * @param string $query
     * @param string $abstractSku
     * @param int $rank
     * @param string $storeName
     * @param string $localeName
     */
    public function writeImpressionEvent(string $query, string $abstractSku, int $rank, string $storeName, string $localeName): void;

    /**
     * @api
     *
     * @param string $query
     * @param string $abstractSku
     * @param int $rank
     * @param string $storeName
     * @param string $localeName
     */
    public function writeClickEvent(string $query, string $abstractSku, int $rank, string $storeName, string $localeName): void;

    /**
     * @api
     *
     * @param \DateTimeInterface $since
     *
     * @return array{impressionsRolledUp: int, clicksRolledUp: int, rawEventsDeleted: int}
     */
    public function rollUp(DateTimeInterface $since): array;

    /**
     * @api
     *
     * @param string $storeName
     * @param string $localeName
     */
    public function emitProductMetricCsv(string $storeName, string $localeName): int;

    /**
     * @api
     *
     * @return array{impressionsDrained: int, clicksDrained: int, cartAddsDrained: int}
     */
    public function drainQueue(): array;

    /**
     * Called directly from a Zed-side `OrderPostSavePluginInterface` plugin (order persistence already
     * happens in Zed, so this bypasses the Storage-KV relay entirely -- see the search-signals plan's
     * "publish is triggered wherever the action naturally happens" reasoning). Deliberately unattributed
     * to any search term (channel 3b's "small version"). Fans out across every locale of `$storeName`
     * internally (resolved via the Zed Store facade) -- the `QuoteTransfer`/`CartChangeTransfer` a Zed
     * plugin receives over the wire from Yves only ever carries a bare `StoreTransfer` (id + name), never
     * the full store config with locales, so callers must not try to read `getAvailableLocaleIsoCodes()`
     * off it themselves.
     *
     * @api
     *
     * @param string $abstractSku
     * @param string $storeName
     * @param int $quantity
     */
    public function incrementOrderCount(string $abstractSku, string $storeName, int $quantity): void;

    /**
     * Called directly from a Zed-side `ItemExpanderPluginInterface` plugin (persistent-cart add-to-cart
     * requests are proxied to Zed, so this bypasses the Storage-KV relay entirely -- the Client-side
     * `CartChangeRequestExpanderPluginInterface` publisher only ever fires for the local/session quote
     * path, which persistent-cart environments never take). Deliberately unattributed to any search term
     * (channel 3b's "small version"). Fans out across every locale of `$storeName` internally, same
     * reasoning as {@see incrementOrderCount()}.
     *
     * @api
     *
     * @param string $abstractSku
     * @param string $storeName
     * @param int $quantity
     */
    public function incrementCartAddCount(string $abstractSku, string $storeName, int $quantity): void;

    /**
     * Per store+locale, all-time coverage of every metric this package produces -- the Zed "Overview"
     * page's data source. See {@see \SprykerCommunity\Zed\SearchSignals\Business\Coverage\MetricCoverageReaderInterface::getOverview()}.
     *
     * @api
     *
     * @return array<int, array{storeName: string, localeName: string, distinctProductCount: int, impressionCount: int, clickCount: int, cartAddCount: int, orderCount: int}>
     */
    public function getMetricCoverageOverview(): array;

    /**
     * Channel 2 (see the search-signals plan): runs every registered
     * {@see \SprykerCommunity\Zed\SearchSignals\Dependency\Plugin\SearchSignalsMetricTransformerPluginInterface}
     * for `$storeName` and writes the result to that store's own local-metric CSV. Returns 0 (no-op,
     * not an error) when no transformer plugins are registered -- a project that has not opted into
     * Channel 2 at all.
     *
     * @api
     *
     * @param string $storeName
     *
     * @return int Number of rows written.
     */
    public function transformLocalMetrics(string $storeName): int;
}
