<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchSignalsWidget\ClickUrl;

interface ClickUrlBuilderInterface
{
    /**
     * @param string $destinationUrl The real PDP URL the visitor should land on.
     * @param string $query
     * @param string $abstractSku
     * @param int $rank
     *
     * @return string The `/search-signals/click?sst=...&to=...` URL to render instead of $destinationUrl.
     */
    public function build(string $destinationUrl, string $query, string $abstractSku, int $rank): string;
}
