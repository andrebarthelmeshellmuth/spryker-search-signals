<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Emit;

interface ProductMetricCsvWriterInterface
{
    /**
     * Sums each product's rollup counts over the configured emit window, shrinks the resulting CTR
     * (see {@see \SprykerCommunity\Zed\SearchSignals\Business\Shrinkage\BayesianShrinkageCalculator}),
     * and writes one `abstract_sku,metric_name,raw_value,store,locale` row per product to the configured
     * output path -- search-ranking's own existing CSV contract, unchanged (see the search-signals plan's
     * "one sink, three producers" decision).
     *
     * @param string $storeName
     * @param string $localeName
     *
     * @return int Number of rows written.
     */
    public function emit(string $storeName, string $localeName): int;
}
