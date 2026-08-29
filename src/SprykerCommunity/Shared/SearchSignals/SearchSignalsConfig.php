<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Shared\SearchSignals;

class SearchSignalsConfig
{
    /**
     * KV-store key prefix events are written under from the Client layer (e.g. Yves) and later drained
     * from by the Zed console command -- a poor-man's queue built on the Storage client's plain
     * get/set/scanKeys/deleteMulti surface, since Spryker's generic Redis client exposes no list/queue
     * primitives (no rpush/lpop) and RabbitMQ credentials deliberately never reach the customer-facing
     * tier. One Storage key per event; ordering doesn't matter since rollup aggregates every pending
     * event regardless of arrival order.
     *
     * @var string
     */
    public const STORAGE_KEY_PREFIX_IMPRESSION_EVENT = 'search_signals:impression:';

    /**
     * @var string
     */
    public const STORAGE_KEY_PREFIX_CLICK_EVENT = 'search_signals:click:';

    /**
     * Channel 3b's "small version" (decided 2026-08-23): deliberately unattributed to any search term --
     * see {@see \SprykerCommunity\Zed\SearchSignals\Persistence\Propel\Schema\spy_search_signals.schema.xml}'s
     * cart_add_count/order_count columns. No raw per-event table for these; the drain step increments the
     * daily bucket directly.
     *
     * @var string
     */
    public const STORAGE_KEY_PREFIX_CART_ADD_EVENT = 'search_signals:cart_add:';

    /**
     * @var string
     */
    public const STORAGE_KEY_PREFIX_ORDER_EVENT = 'search_signals:order:';

    /**
     * @var string
     */
    public const EMIT_METRIC_NAME_CART_ADD = 'cart_add';

    /**
     * @var string
     */
    public const EMIT_METRIC_NAME_ORDER = 'order';

    /**
     * Catalog search request parameter carrying the raw query string -- same convention search-debug's
     * own config already documents (`REQUEST_PARAM_SEARCH_STRING = 'q'`).
     *
     * @var string
     */
    public const REQUEST_PARAM_SEARCH_STRING = 'q';

    /**
     * @var string
     */
    public const CLICK_REDIRECT_ROUTE = 'search-signals/click';

    /**
     * @var string
     */
    public const CLICK_TOKEN_PARAM = 'sst';

    /**
     * Default Bayesian-shrinkage prior weight (the "alpha" in `(clicks + alpha * prior) / (impressions + alpha)`),
     * see {@see \SprykerCommunity\Zed\SearchSignals\Business\Shrinkage\BayesianShrinkageCalculator}.
     *
     * Calibrated 2026-08-23 by actually running the numbers, not picked arbitrarily: at the originally-guessed
     * `alpha=10` (with `prior=0.02`), a pathological 1-impression/1-click product (raw CTR 1.0) shrinks to
     * 0.109 -- a huge reduction from 1.0, but STILL above a genuinely well-supported 50-click/1000-impression
     * product's 0.0497. `alpha=50` is the crossover point where the pathological case finally lands below the
     * well-supported one, while a real 3-click/40-impression product (true CTR 7.5%, a plausible niche-query
     * volume) still keeps meaningful signal (shrinks to 0.044, not crushed flat to the prior). Not a settled
     * constant -- recalibrate against the real per-store prior distribution once genuine traffic data exists.
     *
     * @var float
     */
    public const DEFAULT_SHRINKAGE_ALPHA = 50.0;

    /**
     * Site-wide mean CTR, used as the shrinkage prior when a store/locale has no catalogue-wide average yet.
     *
     * @var float
     */
    public const DEFAULT_SHRINKAGE_PRIOR = 0.02;

    /**
     * @api
     */
    public function getShrinkageAlpha(): float
    {
        return static::DEFAULT_SHRINKAGE_ALPHA;
    }

    /**
     * @api
     */
    public function getShrinkagePrior(): float
    {
        return static::DEFAULT_SHRINKAGE_PRIOR;
    }
}
