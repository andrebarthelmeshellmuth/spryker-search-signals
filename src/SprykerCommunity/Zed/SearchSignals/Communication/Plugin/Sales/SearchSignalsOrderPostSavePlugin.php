<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Communication\Plugin\Sales;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\SaveOrderTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\SalesExtension\Dependency\Plugin\OrderPostSavePluginInterface;

/**
 * Channel 3b's "small version" (see the search-signals plan): deliberately unattributed order counter.
 * Order persistence already happens in Zed, so this calls the Facade directly -- no Storage-KV relay
 * needed at all, unlike impression/click/cart-add capture, which are Yves-triggered and therefore route
 * through that relay instead. "Publish is triggered wherever the action naturally happens."
 *
 * The locale fan-out lives in the facade, not here: `QuoteTransfer` carries no locale of its own (it's a
 * Yves/request-time concern, not persisted per order), and its `StoreTransfer` only carries what Yves
 * serialized over the wire (id + name), never the full store config -- so
 * `getAvailableLocaleIsoCodes()` on it is always empty here, same reasoning as the cart-add expander.
 *
 * @method \SprykerCommunity\Zed\SearchSignals\Business\SearchSignalsFacadeInterface getFacade()
 */
class SearchSignalsOrderPostSavePlugin extends AbstractPlugin implements OrderPostSavePluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SaveOrderTransfer $saveOrderTransfer
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     */
    public function execute(SaveOrderTransfer $saveOrderTransfer, QuoteTransfer $quoteTransfer): SaveOrderTransfer
    {
        $storeName = $quoteTransfer->getStore()?->getName();

        if ($storeName === null) {
            return $saveOrderTransfer;
        }

        foreach ($saveOrderTransfer->getOrderItems() as $itemTransfer) {
            $abstractSku = $itemTransfer->getAbstractSku() ?? $itemTransfer->getSku();

            if ($abstractSku === null) {
                continue;
            }

            $quantity = (int)($itemTransfer->getQuantity() ?? 1);

            $this->getFacade()->incrementOrderCount($abstractSku, $storeName, $quantity);
        }

        return $saveOrderTransfer;
    }
}
