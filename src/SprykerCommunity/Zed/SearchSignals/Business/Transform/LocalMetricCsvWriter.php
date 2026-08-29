<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Transform;

use SprykerCommunity\Zed\SearchSignals\Dependency\Facade\SearchSignalsToStoreFacadeInterface;
use SprykerCommunity\Zed\SearchSignals\SearchSignalsConfig;

class LocalMetricCsvWriter implements LocalMetricCsvWriterInterface
{
    /**
     * @param array<\SprykerCommunity\Zed\SearchSignals\Dependency\Plugin\SearchSignalsMetricTransformerPluginInterface> $metricTransformerPlugins
     * @param \SprykerCommunity\Zed\SearchSignals\Dependency\Facade\SearchSignalsToStoreFacadeInterface $storeFacade
     * @param \SprykerCommunity\Zed\SearchSignals\SearchSignalsConfig $config
     */
    public function __construct(
        protected array $metricTransformerPlugins,
        protected SearchSignalsToStoreFacadeInterface $storeFacade,
        protected SearchSignalsConfig $config,
    ) {
    }

    public function write(string $storeName): int
    {
        if ($this->metricTransformerPlugins === []) {
            return 0;
        }

        $localeNames = $this->storeFacade->getStoreByName($storeName)->getAvailableLocaleIsoCodes();
        $rows = ['abstract_sku,metric_name,raw_value,store,locale'];
        $rowCount = 0;

        foreach ($this->metricTransformerPlugins as $metricTransformerPlugin) {
            $metricName = $metricTransformerPlugin->getMetricName();

            foreach ($metricTransformerPlugin->transform($storeName) as $abstractSku => $rawValue) {
                foreach ($localeNames as $localeName) {
                    $rows[] = sprintf('%s,%s,%s,%s,"%s"', $abstractSku, $metricName, $rawValue, $storeName, $localeName);
                    $rowCount++;
                }
            }
        }

        $this->writeCsv($storeName, $rows);

        return $rowCount;
    }

    /**
     * @param array<int, string> $rows
     */
    protected function writeCsv(string $storeName, array $rows): void
    {
        $outputPath = $this->config->getLocalMetricCsvOutputPath($storeName);
        $outputDirectory = dirname($outputPath);

        if (!is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0755, true);
        }

        file_put_contents($outputPath, implode("\n", $rows) . "\n");
    }
}
