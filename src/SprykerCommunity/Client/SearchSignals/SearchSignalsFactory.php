<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals;

use Spryker\Client\Kernel\AbstractFactory;
use SprykerCommunity\Client\SearchSignals\Dependency\Client\SearchSignalsToLocaleClientInterface;
use SprykerCommunity\Client\SearchSignals\Dependency\Client\SearchSignalsToStorageClientInterface;
use SprykerCommunity\Client\SearchSignals\Dependency\Client\SearchSignalsToStoreClientInterface;
use SprykerCommunity\Client\SearchSignals\EventPublisher\CartAddEventPublisher;
use SprykerCommunity\Client\SearchSignals\EventPublisher\CartAddEventPublisherInterface;
use SprykerCommunity\Client\SearchSignals\EventPublisher\ClickEventPublisher;
use SprykerCommunity\Client\SearchSignals\EventPublisher\ClickEventPublisherInterface;
use SprykerCommunity\Client\SearchSignals\ImpressionPublisher\ImpressionEventPublisher;
use SprykerCommunity\Client\SearchSignals\ImpressionPublisher\ImpressionEventPublisherInterface;

class SearchSignalsFactory extends AbstractFactory
{
    public function createImpressionEventPublisher(): ImpressionEventPublisherInterface
    {
        return new ImpressionEventPublisher($this->getStorageClient());
    }

    public function createClickEventPublisher(): ClickEventPublisherInterface
    {
        return new ClickEventPublisher($this->getStorageClient());
    }

    public function createCartAddEventPublisher(): CartAddEventPublisherInterface
    {
        return new CartAddEventPublisher($this->getStorageClient());
    }

    public function getStorageClient(): SearchSignalsToStorageClientInterface
    {
        return $this->getProvidedDependency(SearchSignalsDependencyProvider::CLIENT_STORAGE);
    }

    public function getStoreClient(): SearchSignalsToStoreClientInterface
    {
        return $this->getProvidedDependency(SearchSignalsDependencyProvider::CLIENT_STORE);
    }

    public function getLocaleClient(): SearchSignalsToLocaleClientInterface
    {
        return $this->getProvidedDependency(SearchSignalsDependencyProvider::CLIENT_LOCALE);
    }
}
