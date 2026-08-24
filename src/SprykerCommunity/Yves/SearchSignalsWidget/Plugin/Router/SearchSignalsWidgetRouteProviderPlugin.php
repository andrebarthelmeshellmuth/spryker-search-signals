<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchSignalsWidget\Plugin\Router;

use Spryker\Shared\Config\Config;
use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConstants;

class SearchSignalsWidgetRouteProviderPlugin extends AbstractRouteProviderPlugin
{
    /**
     * @var string
     */
    public const ROUTE_NAME_CHECK_INSTALLATION = 'search-signals-widget/check-installation';

    /**
     * @param \Spryker\Yves\Router\Route\RouteCollection $routeCollection
     */
    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute('/' . SearchSignalsConfig::CLICK_REDIRECT_ROUTE, 'SearchSignalsWidget', 'Click', 'indexAction');
        $routeCollection->add(SearchSignalsConfig::CLICK_REDIRECT_ROUTE, $route);

        $this->addCheckInstallationRoute($routeCollection);

        return $routeCollection;
    }

    /**
     * Only registered when {@see SearchSignalsConstants::IS_CHECK_INSTALLATION_PAGE_ENABLED} allows it
     * (default: no) — see that constant for why.
     *
     * @param \Spryker\Yves\Router\Route\RouteCollection $routeCollection
     */
    protected function addCheckInstallationRoute(RouteCollection $routeCollection): void
    {
        if (!Config::get(SearchSignalsConstants::IS_CHECK_INSTALLATION_PAGE_ENABLED, false)) {
            return;
        }

        $checkInstallationRoute = $this->buildRoute('/search-signals-widget/check-installation', 'SearchSignalsWidget', 'CheckInstallation', 'indexAction');
        $routeCollection->add(static::ROUTE_NAME_CHECK_INSTALLATION, $checkInstallationRoute);
    }
}
