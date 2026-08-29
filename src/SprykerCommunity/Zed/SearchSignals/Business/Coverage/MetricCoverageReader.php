<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Coverage;

use DateTimeImmutable;
use SprykerCommunity\Zed\SearchSignals\Dependency\Facade\SearchSignalsToStoreFacadeInterface;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsRepositoryInterface;

class MetricCoverageReader implements MetricCoverageReaderInterface
{
    /**
     * All-time window -- see the interface docblock for why this is a coverage overview, not a trend.
     *
     * @var string
     */
    protected const ALL_TIME_SINCE = '1970-01-01';

    /**
     * @param \SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsRepositoryInterface $repository
     * @param \SprykerCommunity\Zed\SearchSignals\Dependency\Facade\SearchSignalsToStoreFacadeInterface $storeFacade
     */
    public function __construct(
        protected SearchSignalsRepositoryInterface $repository,
        protected SearchSignalsToStoreFacadeInterface $storeFacade,
    ) {
    }

    public function getOverview(): array
    {
        $allTimeSince = new DateTimeImmutable(static::ALL_TIME_SINCE);
        $overview = [];

        foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
            $storeName = (string)$storeTransfer->getName();

            foreach ($storeTransfer->getAvailableLocaleIsoCodes() as $localeName) {
                $totals = $this->repository->getProductCtrTotals($storeName, (string)$localeName, $allTimeSince);

                $overview[] = [
                    'storeName' => $storeName,
                    'localeName' => (string)$localeName,
                    'distinctProductCount' => count($totals),
                    'impressionCount' => $this->sumColumn($totals, 'impressionCount'),
                    'clickCount' => $this->sumColumn($totals, 'clickCount'),
                    'cartAddCount' => $this->sumColumn($totals, 'cartAddCount'),
                    'orderCount' => $this->sumColumn($totals, 'orderCount'),
                ];
            }
        }

        return $overview;
    }

    /**
     * @param array<string, array{impressionCount: int, clickCount: int, cartAddCount: int, orderCount: int}> $totals
     * @param string $column
     */
    protected function sumColumn(array $totals, string $column): int
    {
        $sum = 0;

        foreach ($totals as $row) {
            $sum += $row[$column];
        }

        return $sum;
    }
}
