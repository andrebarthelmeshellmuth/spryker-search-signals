<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals\Dependency\Client;

interface SearchSignalsToStorageClientInterface
{
    /**
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value): void;
}
