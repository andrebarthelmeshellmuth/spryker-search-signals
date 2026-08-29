<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchSignalsWidget;

use Spryker\Yves\Kernel\AbstractBundleConfig;

class SearchSignalsWidgetConfig extends AbstractBundleConfig
{
    /**
     * Same secret as the Zed side's {@see \SprykerCommunity\Zed\SearchSignals\SearchSignalsConfig::getClickTokenSecret()}
     * -- see that method's docblock for why there's no baked-in default.
     *
     * @api
     */
    public function getClickTokenSecret(): string
    {
        return (string)getenv('SEARCH_SIGNALS_CLICK_TOKEN_SECRET');
    }

    /**
     * @api
     */
    public function getClickTokenMaxAgeSeconds(): int
    {
        return 3600;
    }
}
