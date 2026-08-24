<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Communication;

use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use SprykerCommunity\Zed\SearchSignals\Dependency\Client\SearchSignalsToStorageClientInterface;
use SprykerCommunity\Zed\SearchSignals\SearchSignalsDependencyProvider;

/**
 * @method \SprykerCommunity\Zed\SearchSignals\Business\SearchSignalsFacadeInterface getFacade()
 */
class SearchSignalsCommunicationFactory extends AbstractCommunicationFactory
{
    public function getStorageClient(): SearchSignalsToStorageClientInterface
    {
        return $this->getProvidedDependency(SearchSignalsDependencyProvider::CLIENT_STORAGE);
    }
}
