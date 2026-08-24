<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals;

use Spryker\Zed\Kernel\AbstractBundleConfig;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig as SharedSearchSignalsConfig;

class SearchSignalsConfig extends AbstractBundleConfig
{
    /**
     * Raw events older than this are deleted by the rollup console command, once they've already been
     * folded into the daily/weekly buckets (see the search-signals plan's P1/P3 split).
     *
     * @var int
     */
    protected const RAW_EVENT_RETENTION_DAYS = 90;

    /**
     * Emit (P4) sums rollup buckets over this trailing window -- a flat window for v1, per the plan
     * (exponential decay is deferred to v1.1).
     *
     * @var int
     */
    protected const EMIT_WINDOW_DAYS = 90;

    /**
     * @api
     */
    public function getRawEventRetentionDays(): int
    {
        return static::RAW_EVENT_RETENTION_DAYS;
    }

    /**
     * @api
     */
    public function getEmitWindowDays(): int
    {
        return static::EMIT_WINDOW_DAYS;
    }

    /**
     * @api
     */
    public function getShrinkageAlpha(): float
    {
        return (new SharedSearchSignalsConfig())->getShrinkageAlpha();
    }

    /**
     * @api
     */
    public function getShrinkagePrior(): float
    {
        return (new SharedSearchSignalsConfig())->getShrinkagePrior();
    }

    /**
     * Where {@see \SprykerCommunity\Zed\SearchSignals\Business\Emit\ProductMetricCsvWriter} writes the
     * `abstract_sku,metric_name,raw_value,store,locale` CSV -- the project wires this path into its own
     * data-import config (e.g. `data/import/local/full_EU.yml`) as a `search-ranking-product-metric`
     * action's `source`, same as any other CSV. See the package README.
     *
     * @api
     */
    public function getEmitCsvOutputPath(): string
    {
        return APPLICATION_ROOT_DIR . '/data/export/search_signals_product_metric.csv';
    }

    /**
     * @api
     */
    public function getEmitMetricName(): string
    {
        return 'ctr';
    }

    /**
     * Channel 2's own output CSV, kept separate from {@see getEmitCsvOutputPath()} (channel 3a/3b's
     * output) so the two independently-scheduled console commands never clobber each other's file. One
     * file per store -- the underlying local-data metrics (stock, delivery time) aren't locale-specific,
     * so there is nothing to split further. The project wires this path into its own data-import config
     * as its own `search-ranking-product-metric` action, same as {@see getEmitCsvOutputPath()}.
     *
     * @api
     */
    public function getLocalMetricCsvOutputPath(string $storeName): string
    {
        return APPLICATION_ROOT_DIR . '/data/export/search_signals_local_metric_' . strtolower($storeName) . '.csv';
    }

    /**
     * Secret for {@see \SprykerCommunity\Shared\SearchSignals\ClickToken\ClickTokenCodec}. Must be set via
     * project config (e.g. an env var) -- there is deliberately no baked-in default, unlike every other
     * setting here: a shared, guessable secret would let anyone forge click events.
     *
     * @api
     */
    public function getClickTokenSecret(): string
    {
        return (string)getenv('SEARCH_SIGNALS_CLICK_TOKEN_SECRET');
    }

    /**
     * @api
     */
    public function getClickTokenMaxAgeSeconds(): int
    {
        return 3600;
    }
}
