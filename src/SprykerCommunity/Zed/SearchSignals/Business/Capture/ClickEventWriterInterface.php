<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Capture;

/**
 * Writes an already-verified click event -- token decoding/verification happens in Yves (see
 * {@see \SprykerCommunity\Shared\SearchSignals\ClickToken\ClickTokenCodec}), before the event is ever
 * queued. Only a successfully-decoded token (i.e. one Yves itself, holding the shared secret, could
 * verify) ever reaches this writer -- there is nothing left to verify here.
 */
interface ClickEventWriterInterface
{
    /**
     * @param string $query
     * @param string $abstractSku
     * @param int $rank
     * @param string $storeName
     * @param string $localeName
     */
    public function write(string $query, string $abstractSku, int $rank, string $storeName, string $localeName): void;
}
