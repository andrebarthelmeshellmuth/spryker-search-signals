<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Counter;

use DateTime;
use SprykerCommunity\Zed\SearchSignals\Dependency\Facade\SearchSignalsToStoreFacadeInterface;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface;

class ProductCounterIncrementer implements ProductCounterIncrementerInterface
{
    /**
     * @param \SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface $entityManager
     * @param \SprykerCommunity\Zed\SearchSignals\Dependency\Facade\SearchSignalsToStoreFacadeInterface $storeFacade
     */
    public function __construct(
        protected SearchSignalsEntityManagerInterface $entityManager,
        protected SearchSignalsToStoreFacadeInterface $storeFacade,
    ) {
    }

    public function incrementCartAddCount(string $abstractSku, string $storeName, int $quantity): void
    {
        $bucketDate = new DateTime();

        foreach ($this->storeFacade->getStoreByName($storeName)->getAvailableLocaleIsoCodes() as $localeName) {
            $this->entityManager->incrementProductCartAddCount($abstractSku, $storeName, $localeName, $bucketDate, $quantity);
        }
    }

    public function incrementOrderCount(string $abstractSku, string $storeName, int $quantity): void
    {
        $bucketDate = new DateTime();

        foreach ($this->storeFacade->getStoreByName($storeName)->getAvailableLocaleIsoCodes() as $localeName) {
            $this->entityManager->incrementProductOrderCount($abstractSku, $storeName, $localeName, $bucketDate, $quantity);
        }
    }
}
