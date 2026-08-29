<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Coverage;

interface MetricCoverageReaderInterface
{
    /**
     * Per store+locale coverage of every metric this package produces (all-time, no `$since` window --
     * this is a "is anything here at all" overview, not a trend), for the Zed GUI overview page and the
     * check-installation console's own drill-down. Rows for a store/locale with zero rows anywhere are
     * still returned (all counts 0) so an adopter can see which store+locale combinations have never
     * captured anything, not just the ones that have.
     *
     * @return array<int, array{storeName: string, localeName: string, distinctProductCount: int, impressionCount: int, clickCount: int, cartAddCount: int, orderCount: int}>
     */
    public function getOverview(): array;
}
