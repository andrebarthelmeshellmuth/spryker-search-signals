<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Emit;

use DateTime;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig as SharedSearchSignalsConfig;
use SprykerCommunity\Zed\SearchSignals\Business\Shrinkage\BayesianShrinkageCalculatorInterface;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsRepositoryInterface;
use SprykerCommunity\Zed\SearchSignals\SearchSignalsConfig;

class ProductMetricCsvWriter implements ProductMetricCsvWriterInterface
{
    /**
     * @param \SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsRepositoryInterface $repository
     * @param \SprykerCommunity\Zed\SearchSignals\Business\Shrinkage\BayesianShrinkageCalculatorInterface $shrinkageCalculator
     * @param \SprykerCommunity\Zed\SearchSignals\SearchSignalsConfig $config
     */
    public function __construct(
        protected SearchSignalsRepositoryInterface $repository,
        protected BayesianShrinkageCalculatorInterface $shrinkageCalculator,
        protected SearchSignalsConfig $config,
    ) {
    }

    public function emit(string $storeName, string $localeName): int
    {
        $since = (new DateTime())->modify(sprintf('-%d days', $this->config->getEmitWindowDays()));
        $productTotals = $this->repository->getProductCtrTotals($storeName, $localeName, $since);

        if ($productTotals === []) {
            return 0;
        }

        $prior = $this->calculateCatalogueWidePrior($productTotals);
        $alpha = $this->config->getShrinkageAlpha();
        $metricName = $this->config->getEmitMetricName();

        $rows = ['abstract_sku,metric_name,raw_value,store,locale'];

        foreach ($productTotals as $abstractSku => $totals) {
            $shrunkCtr = $this->shrinkageCalculator->calculate(
                $totals['clickCount'],
                $totals['impressionCount'],
                $alpha,
                $prior,
            );

            $rows[] = sprintf('%s,%s,%s,%s,"%s"', $abstractSku, $metricName, $shrunkCtr, $storeName, $localeName);

            // Deliberately unattributed raw counts (channel 3b's "small version"), same shape as
            // search-ranking's own top_seller metric -- no shrinkage here, search-ranking's own
            // normalization already handles raw-count scaling the same way it does for top_seller.
            $rows[] = sprintf(
                '%s,%s,%d,%s,"%s"',
                $abstractSku,
                SharedSearchSignalsConfig::EMIT_METRIC_NAME_CART_ADD,
                $totals['cartAddCount'],
                $storeName,
                $localeName,
            );
            $rows[] = sprintf(
                '%s,%s,%d,%s,"%s"',
                $abstractSku,
                SharedSearchSignalsConfig::EMIT_METRIC_NAME_ORDER,
                $totals['orderCount'],
                $storeName,
                $localeName,
            );
        }

        $this->writeCsv($rows);

        return count($productTotals);
    }

    /**
     * Catalogue-wide mean CTR across every product in this window -- a real, computed prior, not just
     * the config default (which is only the fallback for a store/locale with no data at all yet).
     *
     * @param array<string, array{impressionCount: int, clickCount: int, cartAddCount: int, orderCount: int}> $productTotals
     */
    protected function calculateCatalogueWidePrior(array $productTotals): float
    {
        $totalImpressions = 0;
        $totalClicks = 0;

        foreach ($productTotals as $totals) {
            $totalImpressions += $totals['impressionCount'];
            $totalClicks += $totals['clickCount'];
        }

        if ($totalImpressions === 0) {
            return $this->config->getShrinkagePrior();
        }

        return $totalClicks / $totalImpressions;
    }

    /**
     * @param array<int, string> $rows
     */
    protected function writeCsv(array $rows): void
    {
        $outputPath = $this->config->getEmitCsvOutputPath();
        $outputDirectory = dirname($outputPath);

        if (!is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0755, true);
        }

        file_put_contents($outputPath, implode("\n", $rows) . "\n");
    }
}
