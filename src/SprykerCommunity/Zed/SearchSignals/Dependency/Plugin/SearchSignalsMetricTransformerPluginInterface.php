<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Dependency\Plugin;

/**
 * Channel 2 (see the search-signals plan): shapes data the shop already has locally into a ranking
 * signal -- stock level, delivery/lead time, or any other project-specific metric derivable from
 * existing Zed data without any new capture or external source. A pure, synchronous, BATCH transform,
 * never computed at search-request time (see {@see \SprykerCommunity\Zed\SearchSignals\Business\Transform\LocalMetricCsvWriterInterface}).
 *
 * One plugin per metric, registered via {@see \SprykerCommunity\Zed\SearchSignals\SearchSignalsDependencyProvider::getMetricTransformerPlugins()}.
 * search-signals ships ONE naive default implementation per metric (see the `MetricTransformer`
 * namespace in this module's own `Communication\Plugin`), explicitly documented as needing real
 * business-case logic per store -- a real store likely wants something smarter (e.g. weighted by the
 * customer's actual shipping region, warehouse priority rules, etc.), which is exactly why this is a
 * plugin stack a project overrides, not a fixed formula.
 */
interface SearchSignalsMetricTransformerPluginInterface
{
    /**
     * The `metric_name` column value this plugin's rows are written under -- see search-ranking's own
     * `search_ranking_product_metric.csv` contract.
     */
    public function getMetricName(): string;

    /**
     * @param string $storeName
     *
     * @return array<string, float> Keyed by abstract sku.
     */
    public function transform(string $storeName): array;
}
