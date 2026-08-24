<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Dependency\Client;

use Generated\Shared\Transfer\StorageScanResultTransfer;

interface SearchSignalsToStorageClientInterface
{
    /**
     * @param string $pattern
     * @param int $limit
     * @param int|null $cursor
     */
    public function scanKeys(string $pattern, int $limit, ?int $cursor = 0): StorageScanResultTransfer;

    /**
     * @param array<string> $keys
     *
     * @return array<string, mixed>
     */
    public function getMulti(array $keys): array;

    /**
     * @param array<string> $keys
     */
    public function deleteMulti(array $keys): void;
}
