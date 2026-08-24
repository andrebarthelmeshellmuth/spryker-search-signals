<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals;

use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \SprykerCommunity\Client\SearchSignals\SearchSignalsFactory getFactory()
 */
class SearchSignalsClient extends AbstractClient implements SearchSignalsClientInterface
{
    public function publishClickEvent(string $query, string $abstractSku, int $rank, string $storeName, string $localeName): void
    {
        $this->getFactory()->createClickEventPublisher()->publish($query, $abstractSku, $rank, $storeName, $localeName);
    }

    public function getCurrentStoreName(): string
    {
        return $this->getFactory()->getStoreClient()->getCurrentStore()->getNameOrFail();
    }

    public function getCurrentLocaleName(): string
    {
        return $this->getFactory()->getLocaleClient()->getCurrentLocale();
    }
}
