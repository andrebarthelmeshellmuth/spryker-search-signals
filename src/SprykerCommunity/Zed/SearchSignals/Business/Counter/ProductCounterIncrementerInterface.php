<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Counter;

interface ProductCounterIncrementerInterface
{
    /**
     * @param string $abstractSku
     * @param string $storeName
     * @param int $quantity
     */
    public function incrementCartAddCount(string $abstractSku, string $storeName, int $quantity): void;

    /**
     * @param string $abstractSku
     * @param string $storeName
     * @param int $quantity
     */
    public function incrementOrderCount(string $abstractSku, string $storeName, int $quantity): void;
}
