<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals;

interface SearchSignalsClientInterface
{
    /**
     * Publishes an already-verified click event to the queue -- called from
     * {@see \SprykerCommunity\Yves\SearchSignalsWidget\Controller\ClickController} after it decodes and
     * verifies the click token itself.
     *
     * @api
     *
     * @param string $query
     * @param string $abstractSku
     * @param int $rank
     * @param string $storeName
     * @param string $localeName
     */
    public function publishClickEvent(string $query, string $abstractSku, int $rank, string $storeName, string $localeName): void;

    /**
     * Exposed so Yves-side code (e.g. the click-tracking Twig function) can build a signed click token
     * without duplicating its own Store/Locale client bridges -- this Client already has them.
     *
     * @api
     */
    public function getCurrentStoreName(): string;

    /**
     * @api
     */
    public function getCurrentLocaleName(): string;
}
