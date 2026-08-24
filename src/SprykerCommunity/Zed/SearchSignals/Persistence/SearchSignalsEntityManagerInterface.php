<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Persistence;

use DateTimeInterface;

interface SearchSignalsEntityManagerInterface
{
    /**
     * @param string $query
     * @param string $abstractSku
     * @param int $rank
     * @param string $storeName
     * @param string $localeName
     */
    public function createImpressionEvent(
        string $query,
        string $abstractSku,
        int $rank,
        string $storeName,
        string $localeName,
    ): void;

    /**
     * @param string $query
     * @param string $abstractSku
     * @param int $rank
     * @param string $storeName
     * @param string $localeName
     */
    public function createClickEvent(
        string $query,
        string $abstractSku,
        int $rank,
        string $storeName,
        string $localeName,
    ): void;

    /**
     * Upserts one day's (abstractSku, storeName, localeName) bucket, incrementing counts -- never
     * overwriting them, so this stays safe to run more than once over the same events.
     *
     * @param string $abstractSku
     * @param string $storeName
     * @param string $localeName
     * @param \DateTimeInterface $bucketDate
     * @param int $impressionCount
     * @param int $clickCount
     */
    public function incrementProductCtrDailyBucket(
        string $abstractSku,
        string $storeName,
        string $localeName,
        DateTimeInterface $bucketDate,
        int $impressionCount,
        int $clickCount,
    ): void;

    /**
     * @param string $query
     * @param string $abstractSku
     * @param string $storeName
     * @param string $localeName
     * @param \DateTimeInterface $bucketWeek
     * @param int $impressionCount
     * @param int $clickCount
     */
    public function incrementQueryCtrWeeklyBucket(
        string $query,
        string $abstractSku,
        string $storeName,
        string $localeName,
        DateTimeInterface $bucketWeek,
        int $impressionCount,
        int $clickCount,
    ): void;

    /**
     * Deletes every raw event captured before `$cutoffDate` -- called after rollup so raw events never
     * outlive their own short retention window (see the package README for the configured default).
     *
     * @param \DateTimeInterface $cutoffDate
     *
     * @return int Number of deleted rows, across both event tables.
     */
    public function deleteRawEventsBefore(DateTimeInterface $cutoffDate): int;

    /**
     * Channel 3b's "small version" -- deliberately unattributed to any search term, so there is no raw
     * per-event row to insert first; this increments the daily bucket directly (upsert, same
     * always-safe-to-rerun semantics as {@see incrementProductCtrDailyBucket()}).
     *
     * @param string $abstractSku
     * @param string $storeName
     * @param string $localeName
     * @param \DateTimeInterface $bucketDate
     * @param int $count
     */
    public function incrementProductCartAddCount(
        string $abstractSku,
        string $storeName,
        string $localeName,
        DateTimeInterface $bucketDate,
        int $count,
    ): void;

    /**
     * @param string $abstractSku
     * @param string $storeName
     * @param string $localeName
     * @param \DateTimeInterface $bucketDate
     * @param int $count
     */
    public function incrementProductOrderCount(
        string $abstractSku,
        string $storeName,
        string $localeName,
        DateTimeInterface $bucketDate,
        int $count,
    ): void;
}
