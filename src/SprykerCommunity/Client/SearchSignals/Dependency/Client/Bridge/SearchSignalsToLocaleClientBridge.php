<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals\Dependency\Client\Bridge;

use SprykerCommunity\Client\SearchSignals\Dependency\Client\SearchSignalsToLocaleClientInterface;

class SearchSignalsToLocaleClientBridge implements SearchSignalsToLocaleClientInterface
{
    /**
     * @var \Spryker\Client\Locale\LocaleClientInterface
     */
    protected $localeClient;

    /**
     * @param \Spryker\Client\Locale\LocaleClientInterface $localeClient
     */
    public function __construct($localeClient)
    {
        $this->localeClient = $localeClient;
    }

    public function getCurrentLocale(): string
    {
        return $this->localeClient->getCurrentLocale();
    }
}
