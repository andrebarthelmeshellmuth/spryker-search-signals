<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Communication\Plugin\Cart;

use Generated\Shared\Transfer\CartChangeTransfer;
use Spryker\Zed\CartExtension\Dependency\Plugin\ItemExpanderPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * Channel 3b's "small version" (see the search-signals plan): deliberately unattributed cart-add
 * counter. `Operation::addToCart()` only calls the registered `ItemExpanderPluginInterface` stack from
 * its add path (never from remove), so no operation check is needed here. This is the hook that actually
 * fires for this project's cart storage strategy: `PersistentCartFeature` proxies every add-to-cart
 * request through Zed (`DatabaseQuoteStorageStrategy::addItem()` calls `getZedStub()->addItem()`), which
 * never touches the Client-side `CartChangeRequestExpanderPluginInterface` stack at all -- that one only
 * runs for a local/session quote, a path this project's B2B storefront never takes for a logged-in
 * customer. This plugin never mutates `$cartChangeTransfer`, only publishes one increment per item.
 *
 * The locale fan-out lives in the facade, not here: the `StoreTransfer` on the incoming
 * `CartChangeTransfer`'s quote only carries what Yves serialized over the wire (id + name), never the
 * full store config, so `getAvailableLocaleIsoCodes()` on it is always empty here.
 *
 * @method \SprykerCommunity\Zed\SearchSignals\Business\SearchSignalsFacadeInterface getFacade()
 */
class SearchSignalsCartItemExpanderPlugin extends AbstractPlugin implements ItemExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function expandItems(CartChangeTransfer $cartChangeTransfer)
    {
        $storeName = $cartChangeTransfer->getQuote()?->getStore()?->getName();

        if ($storeName === null) {
            return $cartChangeTransfer;
        }

        foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
            $abstractSku = $itemTransfer->getAbstractSku() ?? $itemTransfer->getSku();

            if ($abstractSku === null) {
                continue;
            }

            $quantity = (int)($itemTransfer->getQuantity() ?? 1);

            $this->getFacade()->incrementCartAddCount($abstractSku, $storeName, $quantity);
        }

        return $cartChangeTransfer;
    }
}
