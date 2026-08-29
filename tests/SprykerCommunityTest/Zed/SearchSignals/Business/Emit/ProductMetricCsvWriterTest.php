<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignals\Business\Emit;

use Codeception\Test\Unit;
use SprykerCommunity\Zed\SearchSignals\Business\Emit\ProductMetricCsvWriter;
use SprykerCommunity\Zed\SearchSignals\Business\Shrinkage\BayesianShrinkageCalculator;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsRepositoryInterface;
use SprykerCommunity\Zed\SearchSignals\SearchSignalsConfig;

/**
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchSignals
 * @group Business
 * @group Emit
 * @group ProductMetricCsvWriterTest
 * @group Portable
 */
class ProductMetricCsvWriterTest extends Unit
{
    protected string $outputPath;

    protected function _before(): void
    {
        $this->outputPath = sys_get_temp_dir() . '/search_signals_emit_test_' . uniqid() . '.csv';
    }

    protected function _after(): void
    {
        if (!is_file($this->outputPath)) {
            return;
        }

        unlink($this->outputPath);
    }

    public function testEmitReturnsZeroAndWritesNothingWhenThereAreNoProductTotals(): void
    {
        // Arrange
        $repositoryMock = $this->getMockBuilder(SearchSignalsRepositoryInterface::class)->getMock();
        $repositoryMock->method('getProductCtrTotals')->willReturn([]);
        $writer = new ProductMetricCsvWriter($repositoryMock, new BayesianShrinkageCalculator(), $this->createConfig());

        // Act & Assert
        $this->assertSame(0, $writer->emit('DE', 'de_DE'));
        $this->assertFileDoesNotExist($this->outputPath);
    }

    public function testEmitWritesAShrunkCtrRowPlusRawCartAddAndOrderRowsPerProduct(): void
    {
        // Arrange
        $repositoryMock = $this->getMockBuilder(SearchSignalsRepositoryInterface::class)->getMock();
        $repositoryMock->method('getProductCtrTotals')->willReturn([
            'ABC-123' => ['impressionCount' => 1000, 'clickCount' => 50, 'cartAddCount' => 3, 'orderCount' => 1],
        ]);
        $writer = new ProductMetricCsvWriter($repositoryMock, new BayesianShrinkageCalculator(), $this->createConfig());

        // Act
        $rowCount = $writer->emit('DE', 'de_DE');

        // Assert
        $this->assertSame(1, $rowCount);
        $csv = (string)file_get_contents($this->outputPath);
        $lines = explode("\n", trim($csv));
        $this->assertSame('abstract_sku,metric_name,raw_value,store,locale', $lines[0]);
        // Single product, catalogue-wide prior == its own raw CTR (50/1000 = 0.05) -- alpha cancels out entirely.
        $this->assertSame('ABC-123,ctr,0.05,DE,"de_DE"', $lines[1]);
        $this->assertSame('ABC-123,cart_add,3,DE,"de_DE"', $lines[2]);
        $this->assertSame('ABC-123,order,1,DE,"de_DE"', $lines[3]);
    }

    public function testEmitFallsBackToTheConfiguredPriorWhenTotalImpressionsAreZero(): void
    {
        // Arrange -- a product with zero impressions (shouldn't really happen, but must not divide by zero)
        $repositoryMock = $this->getMockBuilder(SearchSignalsRepositoryInterface::class)->getMock();
        $repositoryMock->method('getProductCtrTotals')->willReturn([
            'ABC-123' => ['impressionCount' => 0, 'clickCount' => 0, 'cartAddCount' => 0, 'orderCount' => 0],
        ]);
        $writer = new ProductMetricCsvWriter($repositoryMock, new BayesianShrinkageCalculator(), $this->createConfig());

        // Act
        $writer->emit('DE', 'de_DE');

        // Assert -- alpha=50, prior=0.02 (config default), (0 + 50*0.02) / (0 + 50) = 0.02
        $csv = (string)file_get_contents($this->outputPath);
        $this->assertStringContainsString('ABC-123,ctr,0.02,DE,"de_DE"', $csv);
    }

    public function testEmitOverwritesThePreviousFileRatherThanAppending(): void
    {
        // Arrange
        $repositoryMock = $this->getMockBuilder(SearchSignalsRepositoryInterface::class)->getMock();
        $repositoryMock->method('getProductCtrTotals')->willReturnOnConsecutiveCalls(
            ['OLD-SKU' => ['impressionCount' => 10, 'clickCount' => 1, 'cartAddCount' => 0, 'orderCount' => 0]],
            ['NEW-SKU' => ['impressionCount' => 10, 'clickCount' => 1, 'cartAddCount' => 0, 'orderCount' => 0]],
        );
        $writer = new ProductMetricCsvWriter($repositoryMock, new BayesianShrinkageCalculator(), $this->createConfig());

        // Act
        $writer->emit('DE', 'de_DE');
        $writer->emit('DE', 'de_DE');

        // Assert
        $csv = (string)file_get_contents($this->outputPath);
        $this->assertStringNotContainsString('OLD-SKU', $csv);
        $this->assertStringContainsString('NEW-SKU', $csv);
    }

    protected function createConfig(): SearchSignalsConfig
    {
        return new class ($this->outputPath) extends SearchSignalsConfig {
            public function __construct(protected string $testOutputPath)
            {
            }

            public function getEmitCsvOutputPath(): string
            {
                return $this->testOutputPath;
            }
        };
    }
}
