<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business;

use DateTimeInterface;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerCommunity\Zed\SearchSignals\Business\SearchSignalsBusinessFactory getFactory()
 */
class SearchSignalsFacade extends AbstractFacade implements SearchSignalsFacadeInterface
{
    public function writeImpressionEvent(string $query, string $abstractSku, int $rank, string $storeName, string $localeName): void
    {
        $this->getFactory()->createImpressionEventWriter()->write($query, $abstractSku, $rank, $storeName, $localeName);
    }

    public function writeClickEvent(string $query, string $abstractSku, int $rank, string $storeName, string $localeName): void
    {
        $this->getFactory()->createClickEventWriter()->write($query, $abstractSku, $rank, $storeName, $localeName);
    }

    public function rollUp(DateTimeInterface $since): array
    {
        return $this->getFactory()->createRollupBuilder()->rollUp($since);
    }

    public function emitProductMetricCsv(string $storeName, string $localeName): int
    {
        return $this->getFactory()->createProductMetricCsvWriter()->emit($storeName, $localeName);
    }

    public function drainQueue(): array
    {
        return $this->getFactory()->createQueueDrainer()->drain();
    }

    public function incrementOrderCount(string $abstractSku, string $storeName, int $quantity): void
    {
        $this->getFactory()->createProductCounterIncrementer()->incrementOrderCount($abstractSku, $storeName, $quantity);
    }

    public function incrementCartAddCount(string $abstractSku, string $storeName, int $quantity): void
    {
        $this->getFactory()->createProductCounterIncrementer()->incrementCartAddCount($abstractSku, $storeName, $quantity);
    }

    public function getMetricCoverageOverview(): array
    {
        return $this->getFactory()->createMetricCoverageReader()->getOverview();
    }

    public function transformLocalMetrics(string $storeName): int
    {
        return $this->getFactory()->createLocalMetricCsvWriter()->write($storeName);
    }
}
