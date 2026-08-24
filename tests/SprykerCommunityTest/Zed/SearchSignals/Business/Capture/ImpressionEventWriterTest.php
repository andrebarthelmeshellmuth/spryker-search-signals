<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignals\Business\Capture;

use Codeception\Test\Unit;
use SprykerCommunity\Zed\SearchSignals\Business\Capture\ImpressionEventWriter;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface;

/**
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchSignals
 * @group Business
 * @group Capture
 * @group ImpressionEventWriterTest
 * @group Portable
 */
class ImpressionEventWriterTest extends Unit
{
    public function testWriteDelegatesToTheEntityManager(): void
    {
        // Arrange
        $entityManagerMock = $this->getMockBuilder(SearchSignalsEntityManagerInterface::class)->getMock();
        $entityManagerMock->expects($this->once())->method('createImpressionEvent')
            ->with('office chair', 'ABC-123', 2, 'DE', 'de_DE');
        $writer = new ImpressionEventWriter($entityManagerMock);

        // Act
        $writer->write('office chair', 'ABC-123', 2, 'DE', 'de_DE');
    }
}
