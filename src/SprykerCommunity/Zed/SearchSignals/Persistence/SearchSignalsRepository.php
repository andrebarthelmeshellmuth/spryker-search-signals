<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Persistence;

use DateTime;
use DateTimeInterface;
use Orm\Zed\SearchSignals\Persistence\Map\SpySearchSignalsProductCtrDailyTableMap;
use Orm\Zed\SearchSignals\Persistence\Map\SpySearchSignalsQueryCtrWeeklyTableMap;
use Orm\Zed\SearchSignals\Persistence\SpySearchSignalsClickEventQuery;
use Orm\Zed\SearchSignals\Persistence\SpySearchSignalsImpressionEventQuery;
use Orm\Zed\SearchSignals\Persistence\SpySearchSignalsProductCtrDailyQuery;
use Orm\Zed\SearchSignals\Persistence\SpySearchSignalsQueryCtrWeeklyQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsPersistenceFactory getFactory()
 */
class SearchSignalsRepository extends AbstractRepository implements SearchSignalsRepositoryInterface
{
    public function getImpressionEventsCapturedSince(DateTimeInterface $since): array
    {
        return $this->mapEventRows(
            SpySearchSignalsImpressionEventQuery::create()
                ->filterByCapturedAt($since->format('Y-m-d H:i:s'), Criteria::GREATER_EQUAL)
                ->find()
                ->toArray(),
        );
    }

    public function getClickEventsCapturedSince(DateTimeInterface $since): array
    {
        return $this->mapEventRows(
            SpySearchSignalsClickEventQuery::create()
                ->filterByCapturedAt($since->format('Y-m-d H:i:s'), Criteria::GREATER_EQUAL)
                ->find()
                ->toArray(),
        );
    }

    public function getProductCtrTotals(string $storeName, string $localeName, DateTimeInterface $since): array
    {
        $rows = SpySearchSignalsProductCtrDailyQuery::create()
            ->filterByStoreName($storeName)
            ->filterByLocaleName($localeName)
            ->filterByBucketDate($since->format('Y-m-d'), Criteria::GREATER_EQUAL)
            ->withColumn('SUM(' . SpySearchSignalsProductCtrDailyTableMap::COL_IMPRESSION_COUNT . ')', 'impressionCount')
            ->withColumn('SUM(' . SpySearchSignalsProductCtrDailyTableMap::COL_CLICK_COUNT . ')', 'clickCount')
            ->withColumn('SUM(' . SpySearchSignalsProductCtrDailyTableMap::COL_CART_ADD_COUNT . ')', 'cartAddCount')
            ->withColumn('SUM(' . SpySearchSignalsProductCtrDailyTableMap::COL_ORDER_COUNT . ')', 'orderCount')
            ->groupByAbstractSku()
            ->select(['AbstractSku', 'impressionCount', 'clickCount', 'cartAddCount', 'orderCount'])
            ->find();

        $totals = [];

        foreach ($rows as $row) {
            $totals[$row['AbstractSku']] = [
                'impressionCount' => (int)$row['impressionCount'],
                'clickCount' => (int)$row['clickCount'],
                'cartAddCount' => (int)$row['cartAddCount'],
                'orderCount' => (int)$row['orderCount'],
            ];
        }

        return $totals;
    }

    public function getQueryCtrTotals(string $storeName, string $localeName, DateTimeInterface $since): array
    {
        $rows = SpySearchSignalsQueryCtrWeeklyQuery::create()
            ->filterByStoreName($storeName)
            ->filterByLocaleName($localeName)
            ->filterByBucketWeek($since->format('Y-m-d'), Criteria::GREATER_EQUAL)
            ->withColumn('SUM(' . SpySearchSignalsQueryCtrWeeklyTableMap::COL_IMPRESSION_COUNT . ')', 'impressionCount')
            ->withColumn('SUM(' . SpySearchSignalsQueryCtrWeeklyTableMap::COL_CLICK_COUNT . ')', 'clickCount')
            ->groupByQuery()
            ->groupByAbstractSku()
            ->select(['Query', 'AbstractSku', 'impressionCount', 'clickCount'])
            ->find();

        $totals = [];

        foreach ($rows as $row) {
            $totals[$row['Query'] . '|' . $row['AbstractSku']] = [
                'impressionCount' => (int)$row['impressionCount'],
                'clickCount' => (int)$row['clickCount'],
            ];
        }

        return $totals;
    }

    public function getQueryImpressionVolumes(string $storeName, string $localeName): array
    {
        $rows = SpySearchSignalsQueryCtrWeeklyQuery::create()
            ->filterByStoreName($storeName)
            ->filterByLocaleName($localeName)
            ->withColumn('SUM(' . SpySearchSignalsQueryCtrWeeklyTableMap::COL_IMPRESSION_COUNT . ')', 'impressionCount')
            ->groupByQuery()
            ->select(['Query', 'impressionCount'])
            ->find();

        $volumes = [];

        foreach ($rows as $row) {
            $volumes[$row['Query']] = (int)$row['impressionCount'];
        }

        return $volumes;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array{query: string, abstractSku: string, rank: int, storeName: string, localeName: string, capturedAt: \DateTimeInterface}>
     */
    protected function mapEventRows(array $rows): array
    {
        return array_map(
            static fn (array $row): array => [
                'query' => (string)$row['Query'],
                'abstractSku' => (string)$row['AbstractSku'],
                'rank' => (int)$row['Rank'],
                'storeName' => (string)$row['StoreName'],
                'localeName' => (string)$row['LocaleName'],
                'capturedAt' => new DateTime((string)$row['CapturedAt']),
            ],
            $rows,
        );
    }
}
