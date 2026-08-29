<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchSignalsWidget;

use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;

class SearchSignalsWidgetDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const CLIENT_SEARCH_SIGNALS = 'CLIENT_SEARCH_SIGNALS';

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     */
    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);

        $container->set(static::CLIENT_SEARCH_SIGNALS, fn (Container $container) => $container->getLocator()->searchSignals()->client());

        return $container;
    }
}
