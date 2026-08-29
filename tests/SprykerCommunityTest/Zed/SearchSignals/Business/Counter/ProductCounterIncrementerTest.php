<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignals\Business\Counter;

use Codeception\Test\Unit;
use DateTimeInterface;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerCommunity\Zed\SearchSignals\Business\Counter\ProductCounterIncrementer;
use SprykerCommunity\Zed\SearchSignals\Dependency\Facade\SearchSignalsToStoreFacadeInterface;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface;

/**
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchSignals
 * @group Business
 * @group Counter
 * @group ProductCounterIncrementerTest
 * @group Portable
 */
class ProductCounterIncrementerTest extends Unit
{
    public function testIncrementCartAddCountFansOutAcrossEveryLocaleOfTheStore(): void
    {
        // Arrange
        $storeTransfer = (new StoreTransfer())->setName('DE')->setAvailableLocaleIsoCodes(['de_DE', 'en_US']);
        $storeFacadeMock = $this->getMockBuilder(SearchSignalsToStoreFacadeInterface::class)->getMock();
        $storeFacadeMock->method('getStoreByName')->with('DE')->willReturn($storeTransfer);

        $entityManagerMock = $this->getMockBuilder(SearchSignalsEntityManagerInterface::class)->getMock();
        $entityManagerMock->expects($this->exactly(2))->method('incrementProductCartAddCount')
            ->with(
                'ABC-123',
                'DE',
                $this->callback(fn (string $localeName): bool => in_array($localeName, ['de_DE', 'en_US'], true)),
                $this->isInstanceOf(DateTimeInterface::class),
                2,
            );

        $incrementer = new ProductCounterIncrementer($entityManagerMock, $storeFacadeMock);

        // Act
        $incrementer->incrementCartAddCount('ABC-123', 'DE', 2);
    }

    public function testIncrementOrderCountFansOutAcrossEveryLocaleOfTheStore(): void
    {
        // Arrange
        $storeTransfer = (new StoreTransfer())->setName('DE')->setAvailableLocaleIsoCodes(['de_DE']);
        $storeFacadeMock = $this->getMockBuilder(SearchSignalsToStoreFacadeInterface::class)->getMock();
        $storeFacadeMock->method('getStoreByName')->with('DE')->willReturn($storeTransfer);

        $entityManagerMock = $this->getMockBuilder(SearchSignalsEntityManagerInterface::class)->getMock();
        $entityManagerMock->expects($this->once())->method('incrementProductOrderCount')
            ->with('ABC-123', 'DE', 'de_DE', $this->isInstanceOf(DateTimeInterface::class), 1);

        $incrementer = new ProductCounterIncrementer($entityManagerMock, $storeFacadeMock);

        // Act
        $incrementer->incrementOrderCount('ABC-123', 'DE', 1);
    }

    public function testIncrementCartAddCountWritesNothingWhenTheStoreHasNoLocales(): void
    {
        // Arrange -- an edge case worth pinning: a misconfigured store shouldn't throw, just no-op.
        $storeTransfer = (new StoreTransfer())->setName('XX')->setAvailableLocaleIsoCodes([]);
        $storeFacadeMock = $this->getMockBuilder(SearchSignalsToStoreFacadeInterface::class)->getMock();
        $storeFacadeMock->method('getStoreByName')->willReturn($storeTransfer);

        $entityManagerMock = $this->getMockBuilder(SearchSignalsEntityManagerInterface::class)->getMock();
        $entityManagerMock->expects($this->never())->method('incrementProductCartAddCount');

        $incrementer = new ProductCounterIncrementer($entityManagerMock, $storeFacadeMock);

        // Act
        $incrementer->incrementCartAddCount('ABC-123', 'XX', 1);
    }
}
