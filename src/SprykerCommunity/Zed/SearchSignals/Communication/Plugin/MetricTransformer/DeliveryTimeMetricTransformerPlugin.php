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
 * NAIVE DEFAULT, deliberately more of a stub than {@see StockLevelMetricTransformerPlugin}: Spryker
 * core has no "lead time per warehouse" concept out of the box (unlike stock quantity, which
 * `spryker/stock` already computes), so there is no real per-product number this plugin could compute
 * without project-specific data (a project's own warehouse/carrier config, a PIM field, a flat rate per
 * shipping method). It returns the SAME configured flat value for every abstract sku currently in
 * stock, which is honest about being a placeholder, not a useful signal by itself -- a real store
 * MUST replace this with logic reading its own delivery-time source (see the plan's own worked example:
 * "soonest available warehouse's lead time").
 *
 * NOT registered by default -- see {@see \SprykerCommunity\Zed\SearchSignals\SearchSignalsDependencyProvider::getMetricTransformerPlugins()}.
 */
class DeliveryTimeMetricTransformerPlugin implements SearchSignalsMetricTransformerPluginInterface
{
    /**
     * @var string
     */
    public const METRIC_NAME = 'delivery_time_days';

    /**
     * Placeholder value in days -- copy this class into a project and replace with a real
     * per-product/per-warehouse computation.
     *
     * @var float
     */
    protected const PLACEHOLDER_DELIVERY_TIME_DAYS = 3.0;

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
            if ((float)(string)$stockFacade->calculateProductAbstractStockForStore($abstractSku, $storeTransfer) <= 0.0) {
                continue;
            }

            $rawValues[$abstractSku] = static::PLACEHOLDER_DELIVERY_TIME_DAYS;
        }

        return $rawValues;
    }
}
