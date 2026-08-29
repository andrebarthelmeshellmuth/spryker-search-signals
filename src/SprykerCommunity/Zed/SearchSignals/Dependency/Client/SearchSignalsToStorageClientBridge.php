<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Dependency\Client;

use Generated\Shared\Transfer\StorageScanResultTransfer;

class SearchSignalsToStorageClientBridge implements SearchSignalsToStorageClientInterface
{
    /**
     * @var \Spryker\Client\Storage\StorageClientInterface
     */
    protected $storageClient;

    /**
     * @param \Spryker\Client\Storage\StorageClientInterface $storageClient
     */
    public function __construct($storageClient)
    {
        $this->storageClient = $storageClient;
    }

    public function scanKeys(string $pattern, int $limit, ?int $cursor = 0): StorageScanResultTransfer
    {
        return $this->storageClient->scanKeys($pattern, $limit, $cursor);
    }

    public function getMulti(array $keys): array
    {
        return $this->storageClient->getMulti($keys);
    }

    public function deleteMulti(array $keys): void
    {
        $this->storageClient->deleteMulti($keys);
    }
}
