<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchSignalsWidget\ClickUrl;

use SprykerCommunity\Client\SearchSignals\SearchSignalsClientInterface;
use SprykerCommunity\Shared\SearchSignals\ClickToken\ClickTokenCodec;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig;

class ClickUrlBuilder implements ClickUrlBuilderInterface
{
    /**
     * @param \SprykerCommunity\Shared\SearchSignals\ClickToken\ClickTokenCodec $clickTokenCodec
     * @param \SprykerCommunity\Client\SearchSignals\SearchSignalsClientInterface $searchSignalsClient
     */
    public function __construct(
        protected ClickTokenCodec $clickTokenCodec,
        protected SearchSignalsClientInterface $searchSignalsClient,
    ) {
    }

    public function build(string $destinationUrl, string $query, string $abstractSku, int $rank): string
    {
        // Never track a destination this package's own redirect guard would refuse to honor -- fall back
        // to the plain, untracked link rather than ever risk breaking a real product link.
        if (!$this->isSafeRelativeDestination($destinationUrl)) {
            return $destinationUrl;
        }

        $token = $this->clickTokenCodec->encode(
            $query,
            $abstractSku,
            $rank,
            $this->searchSignalsClient->getCurrentStoreName(),
            $this->searchSignalsClient->getCurrentLocaleName(),
        );

        return sprintf(
            '/%s?%s=%s&to=%s',
            SearchSignalsConfig::CLICK_REDIRECT_ROUTE,
            SearchSignalsConfig::CLICK_TOKEN_PARAM,
            rawurlencode($token),
            rawurlencode($destinationUrl),
        );
    }

    /**
     * Same guard as {@see \SprykerCommunity\Yves\SearchSignalsWidget\Controller\ClickController::isSafeRelativeDestination()}
     * -- kept in sync intentionally, duplicated rather than shared, since one is a build-time check and
     * the other a landing-time check on user-controlled input; they're allowed to diverge later without
     * either accidentally weakening the other.
     *
     * @param string $destination
     */
    protected function isSafeRelativeDestination(string $destination): bool
    {
        return $destination !== ''
            && $destination[0] === '/'
            && !str_starts_with($destination, '//')
            && !str_contains($destination, '\\')
            && parse_url($destination, PHP_URL_HOST) === null;
    }
}
