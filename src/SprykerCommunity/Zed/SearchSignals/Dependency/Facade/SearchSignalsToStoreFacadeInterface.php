<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Dependency\Facade;

use Generated\Shared\Transfer\StoreTransfer;

interface SearchSignalsToStoreFacadeInterface
{
    /**
     * @param string $storeName
     */
    public function getStoreByName(string $storeName): StoreTransfer;

    /**
     * @return array<\Generated\Shared\Transfer\StoreTransfer>
     */
    public function getAllStores(): array;
}
