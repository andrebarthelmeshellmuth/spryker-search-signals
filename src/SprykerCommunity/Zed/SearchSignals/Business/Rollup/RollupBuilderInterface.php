<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Rollup;

use DateTimeInterface;

interface RollupBuilderInterface
{
    /**
     * Folds every raw impression/click event captured since `$since` into the product-daily and
     * query-weekly bucket tables (append-only increments, never overwritten -- safe to re-run over an
     * overlapping window), then deletes raw events older than the configured retention window.
     *
     * @param \DateTimeInterface $since
     *
     * @return array{impressionsRolledUp: int, clicksRolledUp: int, rawEventsDeleted: int}
     */
    public function rollUp(DateTimeInterface $since): array;
}
