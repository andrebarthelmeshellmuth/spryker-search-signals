<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchSignalsWidget;

use Spryker\Yves\Kernel\AbstractFactory;
use SprykerCommunity\Client\SearchSignals\SearchSignalsClientInterface;
use SprykerCommunity\Shared\SearchSignals\ClickToken\ClickTokenCodec;
use SprykerCommunity\Yves\SearchSignalsWidget\ClickUrl\ClickUrlBuilder;
use SprykerCommunity\Yves\SearchSignalsWidget\ClickUrl\ClickUrlBuilderInterface;

/**
 * @method \SprykerCommunity\Yves\SearchSignalsWidget\SearchSignalsWidgetConfig getConfig()
 */
class SearchSignalsWidgetFactory extends AbstractFactory
{
    public function createClickTokenCodec(): ClickTokenCodec
    {
        return new ClickTokenCodec(
            $this->getConfig()->getClickTokenSecret(),
            $this->getConfig()->getClickTokenMaxAgeSeconds(),
        );
    }

    public function createClickUrlBuilder(): ClickUrlBuilderInterface
    {
        return new ClickUrlBuilder($this->createClickTokenCodec(), $this->getSearchSignalsClient());
    }

    public function getSearchSignalsClient(): SearchSignalsClientInterface
    {
        return $this->getProvidedDependency(SearchSignalsWidgetDependencyProvider::CLIENT_SEARCH_SIGNALS);
    }
}
