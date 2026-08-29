<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Persistence;

use DateTime;
use DateTimeInterface;
use Orm\Zed\SearchSignals\Persistence\SpySearchSignalsClickEvent;
use Orm\Zed\SearchSignals\Persistence\SpySearchSignalsClickEventQuery;
use Orm\Zed\SearchSignals\Persistence\SpySearchSignalsImpressionEvent;
use Orm\Zed\SearchSignals\Persistence\SpySearchSignalsImpressionEventQuery;
use Orm\Zed\SearchSignals\Persistence\SpySearchSignalsProductCtrDailyQuery;
use Orm\Zed\SearchSignals\Persistence\SpySearchSignalsQueryCtrWeeklyQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsPersistenceFactory getFactory()
 */
class SearchSignalsEntityManager extends AbstractEntityManager implements SearchSignalsEntityManagerInterface
{
    public function createImpressionEvent(
        string $query,
        string $abstractSku,
        int $rank,
        string $storeName,
        string $localeName,
    ): void {
        (new SpySearchSignalsImpressionEvent())
            ->setQuery($query)
            ->setAbstractSku($abstractSku)
            ->setRank($rank)
            ->setStoreName($storeName)
            ->setLocaleName($localeName)
            ->setCapturedAt(new DateTime())
            ->save();
    }

    public function createClickEvent(
        string $query,
        string $abstractSku,
        int $rank,
        string $storeName,
        string $localeName,
    ): void {
        (new SpySearchSignalsClickEvent())
            ->setQuery($query)
            ->setAbstractSku($abstractSku)
            ->setRank($rank)
            ->setStoreName($storeName)
            ->setLocaleName($localeName)
            ->setCapturedAt(new DateTime())
            ->save();
    }

    public function incrementProductCtrDailyBucket(
        string $abstractSku,
        string $storeName,
        string $localeName,
        DateTimeInterface $bucketDate,
        int $impressionCount,
        int $clickCount,
    ): void {
        $bucketEntity = SpySearchSignalsProductCtrDailyQuery::create()
            ->filterByAbstractSku($abstractSku)
            ->filterByStoreName($storeName)
            ->filterByLocaleName($localeName)
            ->filterByBucketDate($bucketDate->format('Y-m-d'))
            ->findOneOrCreate();

        $bucketEntity
            ->setImpressionCount($bucketEntity->getImpressionCount() + $impressionCount)
            ->setClickCount($bucketEntity->getClickCount() + $clickCount)
            ->save();
    }

    public function incrementQueryCtrWeeklyBucket(
        string $query,
        string $abstractSku,
        string $storeName,
        string $localeName,
        DateTimeInterface $bucketWeek,
        int $impressionCount,
        int $clickCount,
    ): void {
        $bucketEntity = SpySearchSignalsQueryCtrWeeklyQuery::create()
            ->filterByQuery($query)
            ->filterByAbstractSku($abstractSku)
            ->filterByStoreName($storeName)
            ->filterByLocaleName($localeName)
            ->filterByBucketWeek($bucketWeek->format('Y-m-d'))
            ->findOneOrCreate();

        $bucketEntity
            ->setImpressionCount($bucketEntity->getImpressionCount() + $impressionCount)
            ->setClickCount($bucketEntity->getClickCount() + $clickCount)
            ->save();
    }

    public function incrementProductCartAddCount(
        string $abstractSku,
        string $storeName,
        string $localeName,
        DateTimeInterface $bucketDate,
        int $count,
    ): void {
        $bucketEntity = SpySearchSignalsProductCtrDailyQuery::create()
            ->filterByAbstractSku($abstractSku)
            ->filterByStoreName($storeName)
            ->filterByLocaleName($localeName)
            ->filterByBucketDate($bucketDate->format('Y-m-d'))
            ->findOneOrCreate();

        $bucketEntity
            ->setCartAddCount($bucketEntity->getCartAddCount() + $count)
            ->save();
    }

    public function incrementProductOrderCount(
        string $abstractSku,
        string $storeName,
        string $localeName,
        DateTimeInterface $bucketDate,
        int $count,
    ): void {
        $bucketEntity = SpySearchSignalsProductCtrDailyQuery::create()
            ->filterByAbstractSku($abstractSku)
            ->filterByStoreName($storeName)
            ->filterByLocaleName($localeName)
            ->filterByBucketDate($bucketDate->format('Y-m-d'))
            ->findOneOrCreate();

        $bucketEntity
            ->setOrderCount($bucketEntity->getOrderCount() + $count)
            ->save();
    }

    public function deleteRawEventsBefore(DateTimeInterface $cutoffDate): int
    {
        $deletedImpressions = SpySearchSignalsImpressionEventQuery::create()
            ->filterByCapturedAt($cutoffDate->format('Y-m-d H:i:s'), Criteria::LESS_THAN)
            ->delete();

        $deletedClicks = SpySearchSignalsClickEventQuery::create()
            ->filterByCapturedAt($cutoffDate->format('Y-m-d H:i:s'), Criteria::LESS_THAN)
            ->delete();

        return $deletedImpressions + $deletedClicks;
    }
}
