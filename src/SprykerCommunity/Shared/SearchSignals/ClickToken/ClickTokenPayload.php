<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Shared\SearchSignals\ClickToken;

/**
 * A verified, decoded click-token payload -- exactly the search context, nothing about the visitor.
 */
final readonly class ClickTokenPayload
{
    /**
     * @param string $query
     * @param string $abstractSku
     * @param int $rank
     * @param string $storeName
     * @param string $localeName
     * @param int $issuedAtTimestamp
     */
    public function __construct(
        public string $query,
        public string $abstractSku,
        public int $rank,
        public string $storeName,
        public string $localeName,
        public int $issuedAtTimestamp,
    ) {
    }
}
