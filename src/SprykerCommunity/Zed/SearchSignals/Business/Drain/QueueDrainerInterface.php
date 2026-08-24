<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Drain;

interface QueueDrainerInterface
{
    /**
     * Scans the Storage-KV keys the Client layer wrote impression/click events under, decodes each one,
     * writes it into the raw event tables via the existing writers, then deletes the drained keys.
     *
     * @return array{impressionsDrained: int, clicksDrained: int, cartAddsDrained: int}
     */
    public function drain(): array;
}
