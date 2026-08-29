<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Transform;

interface LocalMetricCsvWriterInterface
{
    /**
     * Runs every registered {@see \SprykerCommunity\Zed\SearchSignals\Dependency\Plugin\SearchSignalsMetricTransformerPluginInterface}
     * for `$storeName` and writes one `abstract_sku,metric_name,raw_value,store,locale` row per
     * (product, plugin) pair -- Channel 2, see the search-signals plan. Batch, not read-time, same as
     * Channel 3a's own emit step; unlike that step this has no locale dimension of its own (the
     * underlying data -- stock, delivery time -- isn't locale-specific), so every locale of `$storeName`
     * gets an identical row, resolved via the Store facade the same way channel 3b's counters already
     * do.
     *
     * @param string $storeName
     *
     * @return int Number of rows written.
     */
    public function write(string $storeName): int;
}
