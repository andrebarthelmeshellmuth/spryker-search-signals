<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use SprykerCommunity\Zed\SearchSignals\Dependency\Client\SearchSignalsToStorageClientBridge;
use SprykerCommunity\Zed\SearchSignals\Dependency\Facade\SearchSignalsToStoreFacadeBridge;

class SearchSignalsDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const CLIENT_STORAGE = 'CLIENT_STORAGE';

    /**
     * @var string
     */
    public const FACADE_STORE = 'FACADE_STORE';

    /**
     * @var string
     */
    public const PLUGINS_METRIC_TRANSFORMER = 'PLUGINS_METRIC_TRANSFORMER';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     */
    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);

        $container->set(static::CLIENT_STORAGE, fn (Container $container) => new SearchSignalsToStorageClientBridge($container->getLocator()->storage()->client()));

        $container->set(static::FACADE_STORE, fn (Container $container) => new SearchSignalsToStoreFacadeBridge($container->getLocator()->store()->facade()));

        $container->set(static::PLUGINS_METRIC_TRANSFORMER, fn () => $this->getMetricTransformerPlugins());

        return $container;
    }

    /**
     * The Communication layer has its OWN DI container, separate from the Business layer's -- a key set
     * only in {@see provideBusinessLayerDependencies()} is invisible here. Needed by
     * {@see \SprykerCommunity\Zed\SearchSignals\Communication\Console\SearchSignalsCheckInstallationConsole},
     * which extends `Spryker\Zed\Kernel\Communication\Console\Console` and therefore resolves
     * `getFactory()` against `SearchSignalsCommunicationFactory`, not the Business factory.
     *
     * @param \Spryker\Zed\Kernel\Container $container
     */
    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);

        $container->set(static::CLIENT_STORAGE, fn (Container $container) => new SearchSignalsToStorageClientBridge($container->getLocator()->storage()->client()));

        return $container;
    }

    /**
     * Channel 2 (see the search-signals plan and {@see \SprykerCommunity\Zed\SearchSignals\Dependency\Plugin\SearchSignalsMetricTransformerPluginInterface}):
     * empty by default -- a project opts in by overriding this method in its own project-level
     * `SearchSignalsDependencyProvider` and returning the naive defaults shipped in this module's own
     * `Communication\Plugin\MetricTransformer` namespace (or its own replacements), same override
     * pattern this toolkit already uses for `FacetResultFormatterPlugin`/`FacetQueryExpanderPlugin` in
     * spryker-community/search-variant-facets.
     *
     * @return array<\SprykerCommunity\Zed\SearchSignals\Dependency\Plugin\SearchSignalsMetricTransformerPluginInterface>
     */
    protected function getMetricTransformerPlugins(): array
    {
        return [];
    }
}
