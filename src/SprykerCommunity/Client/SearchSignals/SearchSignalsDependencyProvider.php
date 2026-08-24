<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
use SprykerCommunity\Client\SearchSignals\Dependency\Client\Bridge\SearchSignalsToLocaleClientBridge;
use SprykerCommunity\Client\SearchSignals\Dependency\Client\Bridge\SearchSignalsToStorageClientBridge;
use SprykerCommunity\Client\SearchSignals\Dependency\Client\Bridge\SearchSignalsToStoreClientBridge;

class SearchSignalsDependencyProvider extends AbstractDependencyProvider
{
    /**
     * @var string
     */
    public const CLIENT_STORAGE = 'CLIENT_STORAGE';

    /**
     * @var string
     */
    public const CLIENT_STORE = 'CLIENT_STORE';

    /**
     * @var string
     */
    public const CLIENT_LOCALE = 'CLIENT_LOCALE';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);

        $container->set(static::CLIENT_STORAGE, fn (Container $container) => new SearchSignalsToStorageClientBridge($container->getLocator()->storage()->client()));

        $container->set(static::CLIENT_STORE, fn (Container $container) => new SearchSignalsToStoreClientBridge($container->getLocator()->store()->client()));

        $container->set(static::CLIENT_LOCALE, fn (Container $container) => new SearchSignalsToLocaleClientBridge($container->getLocator()->locale()->client()));

        return $container;
    }
}
