<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignals\Business\Transform;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerCommunity\Zed\SearchSignals\Business\Transform\LocalMetricCsvWriter;
use SprykerCommunity\Zed\SearchSignals\Dependency\Facade\SearchSignalsToStoreFacadeInterface;
use SprykerCommunity\Zed\SearchSignals\Dependency\Plugin\SearchSignalsMetricTransformerPluginInterface;
use SprykerCommunity\Zed\SearchSignals\SearchSignalsConfig;

/**
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchSignals
 * @group Business
 * @group Transform
 * @group LocalMetricCsvWriterTest
 * @group Portable
 */
class LocalMetricCsvWriterTest extends Unit
{
    protected string $outputPath;

    protected function _before(): void
    {
        $this->outputPath = sys_get_temp_dir() . '/search_signals_local_metric_test_' . uniqid() . '.csv';
    }

    protected function _after(): void
    {
        if (!is_file($this->outputPath)) {
            return;
        }

        unlink($this->outputPath);
    }

    public function testWriteReturnsZeroAndSkipsTheStoreFacadeWhenNoPluginsAreRegistered(): void
    {
        // Arrange
        $storeFacadeMock = $this->getMockBuilder(SearchSignalsToStoreFacadeInterface::class)->getMock();
        $storeFacadeMock->expects($this->never())->method('getStoreByName');
        $writer = new LocalMetricCsvWriter([], $storeFacadeMock, $this->createConfig());

        // Act & Assert
        $this->assertSame(0, $writer->write('DE'));
        $this->assertFileDoesNotExist($this->outputPath);
    }

    public function testWriteFansOutOneRowPerPluginResultAndLocale(): void
    {
        // Arrange
        $storeTransfer = (new StoreTransfer())->setName('DE')->setAvailableLocaleIsoCodes(['de_DE', 'en_US']);
        $storeFacadeMock = $this->getMockBuilder(SearchSignalsToStoreFacadeInterface::class)->getMock();
        $storeFacadeMock->method('getStoreByName')->with('DE')->willReturn($storeTransfer);

        $stockPluginMock = $this->getMockBuilder(SearchSignalsMetricTransformerPluginInterface::class)->getMock();
        $stockPluginMock->method('getMetricName')->willReturn('stock_level');
        $stockPluginMock->method('transform')->with('DE')->willReturn(['ABC-123' => 5.0]);

        $writer = new LocalMetricCsvWriter([$stockPluginMock], $storeFacadeMock, $this->createConfig());

        // Act
        $rowCount = $writer->write('DE');

        // Assert
        $this->assertSame(2, $rowCount);
        $csv = (string)file_get_contents($this->outputPath);
        $this->assertStringContainsString('ABC-123,stock_level,5,DE,"de_DE"', $csv);
        $this->assertStringContainsString('ABC-123,stock_level,5,DE,"en_US"', $csv);
    }

    protected function createConfig(): SearchSignalsConfig
    {
        return new class ($this->outputPath) extends SearchSignalsConfig {
            public function __construct(protected string $testOutputPath)
            {
            }

            // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter -- $storeName is mandated by SearchSignalsConfig::getLocalMetricCsvOutputPath().
            public function getLocalMetricCsvOutputPath(string $storeName): string
            {
                return $this->testOutputPath;
            }
        };
    }
}
