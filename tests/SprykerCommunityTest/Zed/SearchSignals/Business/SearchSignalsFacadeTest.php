<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignals\Business;

use Codeception\Test\Unit;
use DateTimeImmutable;
use SprykerCommunity\Zed\SearchSignals\Business\Capture\ClickEventWriterInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Capture\ImpressionEventWriterInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Counter\ProductCounterIncrementerInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Coverage\MetricCoverageReaderInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Drain\QueueDrainerInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Emit\ProductMetricCsvWriterInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Rollup\RollupBuilderInterface;
use SprykerCommunity\Zed\SearchSignals\Business\SearchSignalsBusinessFactory;
use SprykerCommunity\Zed\SearchSignals\Business\SearchSignalsFacade;
use SprykerCommunity\Zed\SearchSignals\Business\Transform\LocalMetricCsvWriterInterface;

/**
 * Every one of this Facade's methods is a pure one-hop delegation to a factory-built collaborator — this
 * test's only job is asserting each hop happens with the right arguments. The real logic behind each
 * collaborator is covered by that collaborator's own dedicated test.
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchSignals
 * @group Business
 * @group SearchSignalsFacadeTest
 * @group Portable
 */
class SearchSignalsFacadeTest extends Unit
{
    public function testWriteImpressionEventDelegatesToTheImpressionEventWriter(): void
    {
        // Arrange
        $writerMock = $this->getMockBuilder(ImpressionEventWriterInterface::class)->getMock();
        $writerMock->expects($this->once())->method('write')->with('office chair', 'ABC-123', 2, 'DE', 'de_DE');
        $facade = $this->createFacade($this->createFactoryMock(['createImpressionEventWriter'], $writerMock));

        // Act
        $facade->writeImpressionEvent('office chair', 'ABC-123', 2, 'DE', 'de_DE');
    }

    public function testWriteClickEventDelegatesToTheClickEventWriter(): void
    {
        // Arrange
        $writerMock = $this->getMockBuilder(ClickEventWriterInterface::class)->getMock();
        $writerMock->expects($this->once())->method('write')->with('office chair', 'ABC-123', 2, 'DE', 'de_DE');
        $facade = $this->createFacade($this->createFactoryMock(['createClickEventWriter'], $writerMock));

        // Act
        $facade->writeClickEvent('office chair', 'ABC-123', 2, 'DE', 'de_DE');
    }

    public function testRollUpDelegatesToTheRollupBuilder(): void
    {
        // Arrange
        $since = new DateTimeImmutable('2026-08-01');
        $rolledUp = ['impressionsRolledUp' => 5, 'clicksRolledUp' => 2, 'rawEventsDeleted' => 7];
        $builderMock = $this->createConfiguredMock(RollupBuilderInterface::class, [
            'rollUp' => $rolledUp,
        ]);
        $facade = $this->createFacade($this->createFactoryMock(['createRollupBuilder'], $builderMock));

        // Act & Assert
        $this->assertSame($rolledUp, $facade->rollUp($since));
    }

    public function testEmitProductMetricCsvDelegatesToTheProductMetricCsvWriter(): void
    {
        // Arrange
        $writerMock = $this->createConfiguredMock(ProductMetricCsvWriterInterface::class, [
            'emit' => 42,
        ]);
        $facade = $this->createFacade($this->createFactoryMock(['createProductMetricCsvWriter'], $writerMock));

        // Act & Assert
        $this->assertSame(42, $facade->emitProductMetricCsv('DE', 'de_DE'));
    }

    public function testDrainQueueDelegatesToTheQueueDrainer(): void
    {
        // Arrange
        $result = ['impressionsDrained' => 3, 'clicksDrained' => 1, 'cartAddsDrained' => 0];
        $drainerMock = $this->createConfiguredMock(QueueDrainerInterface::class, [
            'drain' => $result,
        ]);
        $facade = $this->createFacade($this->createFactoryMock(['createQueueDrainer'], $drainerMock));

        // Act & Assert
        $this->assertSame($result, $facade->drainQueue());
    }

    public function testIncrementOrderCountDelegatesToTheProductCounterIncrementer(): void
    {
        // Arrange
        $incrementerMock = $this->getMockBuilder(ProductCounterIncrementerInterface::class)->getMock();
        $incrementerMock->expects($this->once())->method('incrementOrderCount')->with('ABC-123', 'DE', 2);
        $facade = $this->createFacade($this->createFactoryMock(['createProductCounterIncrementer'], $incrementerMock));

        // Act
        $facade->incrementOrderCount('ABC-123', 'DE', 2);
    }

    public function testIncrementCartAddCountDelegatesToTheProductCounterIncrementer(): void
    {
        // Arrange
        $incrementerMock = $this->getMockBuilder(ProductCounterIncrementerInterface::class)->getMock();
        $incrementerMock->expects($this->once())->method('incrementCartAddCount')->with('ABC-123', 'DE', 1);
        $facade = $this->createFacade($this->createFactoryMock(['createProductCounterIncrementer'], $incrementerMock));

        // Act
        $facade->incrementCartAddCount('ABC-123', 'DE', 1);
    }

    public function testGetMetricCoverageOverviewDelegatesToTheMetricCoverageReader(): void
    {
        // Arrange
        $overview = [
            ['storeName' => 'DE', 'localeName' => 'de_DE', 'distinctProductCount' => 3, 'impressionCount' => 10, 'clickCount' => 2, 'cartAddCount' => 1, 'orderCount' => 0],
        ];
        $readerMock = $this->createConfiguredMock(MetricCoverageReaderInterface::class, [
            'getOverview' => $overview,
        ]);
        $facade = $this->createFacade($this->createFactoryMock(['createMetricCoverageReader'], $readerMock));

        // Act & Assert
        $this->assertSame($overview, $facade->getMetricCoverageOverview());
    }

    public function testTransformLocalMetricsDelegatesToTheLocalMetricCsvWriter(): void
    {
        // Arrange
        $writerMock = $this->createConfiguredMock(LocalMetricCsvWriterInterface::class, [
            'write' => 7,
        ]);
        $facade = $this->createFacade($this->createFactoryMock(['createLocalMetricCsvWriter'], $writerMock));

        // Act & Assert
        $this->assertSame(7, $facade->transformLocalMetrics('DE'));
    }

    protected function createFacade(SearchSignalsBusinessFactory $factoryMock): SearchSignalsFacade
    {
        $facade = new SearchSignalsFacade();
        $facade->setFactory($factoryMock);

        return $facade;
    }

    /**
     * @param array<string> $onlyMethods
     */
    protected function createFactoryMock(array $onlyMethods, mixed $returnValue): SearchSignalsBusinessFactory
    {
        $factoryMock = $this->getMockBuilder(SearchSignalsBusinessFactory::class)
            ->onlyMethods($onlyMethods)
            ->getMock();
        $factoryMock->method($onlyMethods[0])->willReturn($returnValue);

        return $factoryMock;
    }
}
