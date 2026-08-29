<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Communication\Plugin\MetricTransformer;

use Spryker\Zed\Kernel\Locator;
use SprykerCommunity\Zed\SearchSignals\Dependency\Plugin\SearchSignalsMetricTransformerPluginInterface;

/**
 * NAIVE DEFAULT (see the search-signals plan's Channel 2, and the interface's own docblock): raw
 * on-hand stock quantity, summed across every warehouse available to the store, via Spryker core's own
 * {@see \Spryker\Zed\Stock\Business\StockFacadeInterface::calculateProductAbstractStockForStore()}.
 * Explicitly not opinionated about what a "good" stock-based ranking signal looks like -- a real store
 * likely wants something smarter (e.g. a floor/ceiling, a log transform so a 10,000-unit warehouse
 * item doesn't dominate a 10-unit one, or weighting by the customer's actual shipping region). Copy
 * this class into a project and adjust {@see transform()} rather than editing it here.
 *
 * NOT registered by default -- {@see \SprykerCommunity\Zed\SearchSignals\SearchSignalsDependencyProvider::getMetricTransformerPlugins()}
 * returns an empty stack. A project opts in by registering this class (or its own replacement) there.
 *
 * Uses {@see Locator} directly rather than a constructor-injected Bridge: this plugin lives outside
 * this module's own Facade/Factory dependency graph (it is consumed BY the Business layer, not
 * constructed by it), the same reason Zed console commands reach for the same Locator idiom when they
 * need a sibling module's Facade without a full DependencyProvider entry.
 */
class StockLevelMetricTransformerPlugin implements SearchSignalsMetricTransformerPluginInterface
{
    /**
     * @var string
     */
    public const METRIC_NAME = 'stock_level';

    public function getMetricName(): string
    {
        return static::METRIC_NAME;
    }

    public function transform(string $storeName): array
    {
        $productAbstractQueryClass = 'Orm\\Zed\\Product\\Persistence\\SpyProductAbstractQuery';

        if (!class_exists($productAbstractQueryClass)) {
            return [];
        }

        $storeTransfer = Locator::getInstance()->store()->facade()->getStoreByName($storeName);
        $stockFacade = Locator::getInstance()->stock()->facade();

        /** @var array<string> $abstractSkus */
        $abstractSkus = $productAbstractQueryClass::create()->select(['Sku'])->find()->getData();

        $rawValues = [];

        foreach ($abstractSkus as $abstractSku) {
            $rawValues[$abstractSku] = (float)(string)$stockFacade->calculateProductAbstractStockForStore($abstractSku, $storeTransfer);
        }

        return $rawValues;
    }
}
