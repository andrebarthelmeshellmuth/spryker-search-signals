<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals\Plugin\Cart;

use Generated\Shared\Transfer\CartChangeTransfer;
use Spryker\Client\CartExtension\Dependency\Plugin\CartChangeRequestExpanderPluginInterface;
use Spryker\Client\Kernel\AbstractPlugin;

/**
 * Channel 3b's "small version" (see the search-signals plan): deliberately unattributed cart-add
 * counter. This is an "expander" plugin purely for its side effect -- it never mutates
 * `$cartChangeTransfer`, only publishes one event per item. Chosen over
 * `CartOperationPostSavePluginInterface` (the more obvious-looking hook) because that one only receives
 * the *resulting* quote after save, with no way to tell which item(s) this specific request added versus
 * items already in the cart -- this expander instead runs on the real, pre-merge request, which is
 * exactly the items being added right now.
 *
 * @method \SprykerCommunity\Client\SearchSignals\SearchSignalsFactory getFactory()
 */
class SearchSignalsCartAddCartChangeRequestExpanderPlugin extends AbstractPlugin implements CartChangeRequestExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter $params is mandated by CartChangeRequestExpanderPluginInterface.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     * @param array<string, mixed> $params
     */
    public function expand(CartChangeTransfer $cartChangeTransfer, array $params = []): CartChangeTransfer
    {
        $storeName = $this->getFactory()->getStoreClient()->getCurrentStore()->getNameOrFail();
        $localeName = $this->getFactory()->getLocaleClient()->getCurrentLocale();
        $publisher = $this->getFactory()->createCartAddEventPublisher();

        foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
            $abstractSku = $itemTransfer->getAbstractSku() ?? $itemTransfer->getSku();

            if ($abstractSku === null) {
                continue;
            }

            $publisher->publish($abstractSku, $storeName, $localeName);
        }

        return $cartChangeTransfer;
    }
}
