<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Capture;

use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface;

class ImpressionEventWriter implements ImpressionEventWriterInterface
{
    /**
     * @param \SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface $entityManager
     */
    public function __construct(protected SearchSignalsEntityManagerInterface $entityManager)
    {
    }

    public function write(string $query, string $abstractSku, int $rank, string $storeName, string $localeName): void
    {
        $this->entityManager->createImpressionEvent($query, $abstractSku, $rank, $storeName, $localeName);
    }
}
