<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerCommunity\Zed\SearchSignals\Business\Capture\ClickEventWriter;
use SprykerCommunity\Zed\SearchSignals\Business\Capture\ClickEventWriterInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Capture\ImpressionEventWriter;
use SprykerCommunity\Zed\SearchSignals\Business\Capture\ImpressionEventWriterInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Counter\ProductCounterIncrementer;
use SprykerCommunity\Zed\SearchSignals\Business\Counter\ProductCounterIncrementerInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Coverage\MetricCoverageReader;
use SprykerCommunity\Zed\SearchSignals\Business\Coverage\MetricCoverageReaderInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Drain\QueueDrainer;
use SprykerCommunity\Zed\SearchSignals\Business\Drain\QueueDrainerInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Emit\ProductMetricCsvWriter;
use SprykerCommunity\Zed\SearchSignals\Business\Emit\ProductMetricCsvWriterInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Rollup\RollupBuilder;
use SprykerCommunity\Zed\SearchSignals\Business\Rollup\RollupBuilderInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Shrinkage\BayesianShrinkageCalculator;
use SprykerCommunity\Zed\SearchSignals\Business\Shrinkage\BayesianShrinkageCalculatorInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Transform\LocalMetricCsvWriter;
use SprykerCommunity\Zed\SearchSignals\Business\Transform\LocalMetricCsvWriterInterface;
use SprykerCommunity\Zed\SearchSignals\Dependency\Client\SearchSignalsToStorageClientInterface;
use SprykerCommunity\Zed\SearchSignals\Dependency\Facade\SearchSignalsToStoreFacadeInterface;
use SprykerCommunity\Zed\SearchSignals\SearchSignalsDependencyProvider;

/**
 * @method \SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsRepositoryInterface getRepository()
 * @method \SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface getEntityManager()
 * @method \SprykerCommunity\Zed\SearchSignals\SearchSignalsConfig getConfig()
 */
class SearchSignalsBusinessFactory extends AbstractBusinessFactory
{
    public function createImpressionEventWriter(): ImpressionEventWriterInterface
    {
        return new ImpressionEventWriter($this->getEntityManager());
    }

    public function createClickEventWriter(): ClickEventWriterInterface
    {
        return new ClickEventWriter($this->getEntityManager());
    }

    public function createQueueDrainer(): QueueDrainerInterface
    {
        return new QueueDrainer(
            $this->getStorageClient(),
            $this->createImpressionEventWriter(),
            $this->createClickEventWriter(),
            $this->getEntityManager(),
        );
    }

    public function getStorageClient(): SearchSignalsToStorageClientInterface
    {
        return $this->getProvidedDependency(SearchSignalsDependencyProvider::CLIENT_STORAGE);
    }

    public function createRollupBuilder(): RollupBuilderInterface
    {
        return new RollupBuilder($this->getRepository(), $this->getEntityManager(), $this->getConfig());
    }

    public function createProductMetricCsvWriter(): ProductMetricCsvWriterInterface
    {
        return new ProductMetricCsvWriter($this->getRepository(), $this->createBayesianShrinkageCalculator(), $this->getConfig());
    }

    public function createBayesianShrinkageCalculator(): BayesianShrinkageCalculatorInterface
    {
        return new BayesianShrinkageCalculator();
    }

    public function getStoreFacade(): SearchSignalsToStoreFacadeInterface
    {
        return $this->getProvidedDependency(SearchSignalsDependencyProvider::FACADE_STORE);
    }

    public function createProductCounterIncrementer(): ProductCounterIncrementerInterface
    {
        return new ProductCounterIncrementer($this->getEntityManager(), $this->getStoreFacade());
    }

    public function createMetricCoverageReader(): MetricCoverageReaderInterface
    {
        return new MetricCoverageReader($this->getRepository(), $this->getStoreFacade());
    }

    public function createLocalMetricCsvWriter(): LocalMetricCsvWriterInterface
    {
        return new LocalMetricCsvWriter($this->getMetricTransformerPlugins(), $this->getStoreFacade(), $this->getConfig());
    }

    /**
     * @return array<\SprykerCommunity\Zed\SearchSignals\Dependency\Plugin\SearchSignalsMetricTransformerPluginInterface>
     */
    public function getMetricTransformerPlugins(): array
    {
        return $this->getProvidedDependency(SearchSignalsDependencyProvider::PLUGINS_METRIC_TRANSFORMER);
    }
}
