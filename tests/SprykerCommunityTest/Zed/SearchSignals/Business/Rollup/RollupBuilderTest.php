<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignals\Business\Rollup;

use Codeception\Test\Unit;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Rollup\RollupBuilder;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsRepositoryInterface;
use SprykerCommunity\Zed\SearchSignals\SearchSignalsConfig;

/**
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchSignals
 * @group Business
 * @group Rollup
 * @group RollupBuilderTest
 * @group Portable
 */
class RollupBuilderTest extends Unit
{
    public function testRollUpFoldsOneImpressionIntoBothBucketTablesWithAOneZeroSplit(): void
    {
        // Arrange
        $capturedAt = new DateTime('2026-08-19 10:00:00'); // a Wednesday
        $repositoryMock = $this->getMockBuilder(SearchSignalsRepositoryInterface::class)->getMock();
        $repositoryMock->method('getImpressionEventsCapturedSince')->willReturn([
            ['query' => 'chair', 'abstractSku' => 'ABC-123', 'rank' => 2, 'storeName' => 'DE', 'localeName' => 'de_DE', 'capturedAt' => $capturedAt],
        ]);
        $repositoryMock->method('getClickEventsCapturedSince')->willReturn([]);

        $entityManagerMock = $this->getMockBuilder(SearchSignalsEntityManagerInterface::class)->getMock();
        $entityManagerMock->expects($this->once())->method('incrementProductCtrDailyBucket')
            ->with('ABC-123', 'DE', 'de_DE', $capturedAt, 1, 0);
        $entityManagerMock->expects($this->once())->method('incrementQueryCtrWeeklyBucket')
            ->with(
                'chair',
                'ABC-123',
                'DE',
                'de_DE',
                $this->callback(fn (DateTimeInterface $bucketWeek): bool => $bucketWeek->format('Y-m-d') === '2026-08-17'), // the Monday of that week
                1,
                0,
            );
        $entityManagerMock->method('deleteRawEventsBefore')->willReturn(0);

        $rollupBuilder = new RollupBuilder($repositoryMock, $entityManagerMock, new SearchSignalsConfig());

        // Act
        $result = $rollupBuilder->rollUp(new DateTimeImmutable('2026-08-01'));

        // Assert
        $this->assertSame(['impressionsRolledUp' => 1, 'clicksRolledUp' => 0, 'rawEventsDeleted' => 0], $result);
    }

    public function testRollUpFoldsOneClickIntoBothBucketTablesWithAZeroOneSplit(): void
    {
        // Arrange
        $capturedAt = new DateTime('2026-08-19 10:00:00');
        $repositoryMock = $this->getMockBuilder(SearchSignalsRepositoryInterface::class)->getMock();
        $repositoryMock->method('getImpressionEventsCapturedSince')->willReturn([]);
        $repositoryMock->method('getClickEventsCapturedSince')->willReturn([
            ['query' => 'chair', 'abstractSku' => 'ABC-123', 'rank' => 2, 'storeName' => 'DE', 'localeName' => 'de_DE', 'capturedAt' => $capturedAt],
        ]);

        $entityManagerMock = $this->getMockBuilder(SearchSignalsEntityManagerInterface::class)->getMock();
        $entityManagerMock->expects($this->once())->method('incrementProductCtrDailyBucket')
            ->with('ABC-123', 'DE', 'de_DE', $capturedAt, 0, 1);
        $entityManagerMock->expects($this->once())->method('incrementQueryCtrWeeklyBucket')
            ->with('chair', 'ABC-123', 'DE', 'de_DE', $this->isInstanceOf(DateTimeInterface::class), 0, 1);
        $entityManagerMock->method('deleteRawEventsBefore')->willReturn(0);

        $rollupBuilder = new RollupBuilder($repositoryMock, $entityManagerMock, new SearchSignalsConfig());

        // Act
        $result = $rollupBuilder->rollUp(new DateTimeImmutable('2026-08-01'));

        // Assert
        $this->assertSame(['impressionsRolledUp' => 0, 'clicksRolledUp' => 1, 'rawEventsDeleted' => 0], $result);
    }

    public function testRollUpDeletesRawEventsOlderThanTheConfiguredRetentionWindowAndReportsTheCount(): void
    {
        // Arrange
        $repositoryMock = $this->getMockBuilder(SearchSignalsRepositoryInterface::class)->getMock();
        $repositoryMock->method('getImpressionEventsCapturedSince')->willReturn([]);
        $repositoryMock->method('getClickEventsCapturedSince')->willReturn([]);

        $entityManagerMock = $this->getMockBuilder(SearchSignalsEntityManagerInterface::class)->getMock();
        $entityManagerMock->expects($this->once())->method('deleteRawEventsBefore')
            ->with($this->callback(function (DateTimeInterface $cutoff): bool {
                // Config's default retention is 90 days -- the cutoff must be ~90 days in the past, not "now".
                $expected = (new DateTime())->modify('-90 days');

                return abs($cutoff->getTimestamp() - $expected->getTimestamp()) < 60;
            }))
            ->willReturn(42);

        $rollupBuilder = new RollupBuilder($repositoryMock, $entityManagerMock, new SearchSignalsConfig());

        // Act
        $result = $rollupBuilder->rollUp(new DateTimeImmutable('2026-08-01'));

        // Assert
        $this->assertSame(42, $result['rawEventsDeleted']);
    }
}
