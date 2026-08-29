<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals\Dependency\Client\Bridge;

use Generated\Shared\Transfer\StoreTransfer;
use SprykerCommunity\Client\SearchSignals\Dependency\Client\SearchSignalsToStoreClientInterface;

/**
 * Injected explicitly rather than using the static `Spryker\Shared\Kernel\Store::getInstance()` -- that
 * crashes under dynamic store mode (see [[spryker_pns_storage_sync_gotchas]]-adjacent gotcha, confirmed
 * elsewhere in this codebase).
 */
class SearchSignalsToStoreClientBridge implements SearchSignalsToStoreClientInterface
{
    /**
     * @var \Spryker\Client\Store\StoreClientInterface
     */
    protected $storeClient;

    /**
     * @param \Spryker\Client\Store\StoreClientInterface $storeClient
     */
    public function __construct($storeClient)
    {
        $this->storeClient = $storeClient;
    }

    public function getCurrentStore(): StoreTransfer
    {
        return $this->storeClient->getCurrentStore();
    }
}
