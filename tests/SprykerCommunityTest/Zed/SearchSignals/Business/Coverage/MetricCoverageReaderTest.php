<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignals\Business\Coverage;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerCommunity\Zed\SearchSignals\Business\Coverage\MetricCoverageReader;
use SprykerCommunity\Zed\SearchSignals\Dependency\Facade\SearchSignalsToStoreFacadeInterface;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsRepositoryInterface;

/**
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchSignals
 * @group Business
 * @group Coverage
 * @group MetricCoverageReaderTest
 * @group Portable
 */
class MetricCoverageReaderTest extends Unit
{
    public function testGetOverviewReturnsOneRowPerStoreLocaleCombinationWithSummedTotals(): void
    {
        // Arrange
        $storeTransfer = (new StoreTransfer())
            ->setName('DE')
            ->setAvailableLocaleIsoCodes(['de_DE', 'en_US']);

        $storeFacadeMock = $this->getMockBuilder(SearchSignalsToStoreFacadeInterface::class)->getMock();
        $storeFacadeMock->method('getAllStores')->willReturn([$storeTransfer]);

        $totalsByLocale = [
            'de_DE' => [
                'ABC-123' => ['impressionCount' => 10, 'clickCount' => 2, 'cartAddCount' => 1, 'orderCount' => 0],
                'ABC-456' => ['impressionCount' => 5, 'clickCount' => 0, 'cartAddCount' => 0, 'orderCount' => 0],
            ],
            'en_US' => [],
        ];

        // storeName/since only exist below to match getProductCtrTotals()'s real signature positionally --
        // only localeName distinguishes the fixture.
        $repositoryMock = $this->getMockBuilder(SearchSignalsRepositoryInterface::class)->getMock();
        $repositoryMock->method('getProductCtrTotals')
            // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
            ->willReturnCallback(static fn (string $storeName, string $localeName, \DateTimeInterface $since): array => $totalsByLocale[$localeName]);

        $reader = new MetricCoverageReader($repositoryMock, $storeFacadeMock);

        // Act
        $overview = $reader->getOverview();

        // Assert
        $this->assertSame([
            [
                'storeName' => 'DE',
                'localeName' => 'de_DE',
                'distinctProductCount' => 2,
                'impressionCount' => 15,
                'clickCount' => 2,
                'cartAddCount' => 1,
                'orderCount' => 0,
            ],
            [
                'storeName' => 'DE',
                'localeName' => 'en_US',
                'distinctProductCount' => 0,
                'impressionCount' => 0,
                'clickCount' => 0,
                'cartAddCount' => 0,
                'orderCount' => 0,
            ],
        ], $overview);
    }

    public function testGetOverviewReturnsAnEmptyArrayWhenNoStoresAreConfigured(): void
    {
        // Arrange
        $storeFacadeMock = $this->getMockBuilder(SearchSignalsToStoreFacadeInterface::class)->getMock();
        $storeFacadeMock->method('getAllStores')->willReturn([]);

        $repositoryMock = $this->getMockBuilder(SearchSignalsRepositoryInterface::class)->getMock();
        $repositoryMock->expects($this->never())->method('getProductCtrTotals');

        $reader = new MetricCoverageReader($repositoryMock, $storeFacadeMock);

        // Act & Assert
        $this->assertSame([], $reader->getOverview());
    }
}
