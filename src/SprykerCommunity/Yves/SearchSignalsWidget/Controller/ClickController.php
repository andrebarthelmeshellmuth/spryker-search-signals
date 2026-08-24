<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchSignalsWidget\Controller;

use Spryker\Yves\Kernel\Controller\AbstractController;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The SRP -> PDP click-tracking redirect (channel 3a, see the search-signals plan). A product link on the
 * SRP points HERE instead of straight at the PDP, carrying the signed click token plus the real PDP path;
 * this verifies the token, publishes a click event (already-verified data, no re-verification needed
 * downstream -- see {@see \SprykerCommunity\Zed\SearchSignals\Communication\Console\SearchSignalsDrainQueueConsole}),
 * then 302s on to the real page. An invalid/missing/expired token or a non-relative destination still
 * redirects (never a dead end for the visitor), it just skips recording anything.
 *
 * @method \SprykerCommunity\Yves\SearchSignalsWidget\SearchSignalsWidgetFactory getFactory()
 */
class ClickController extends AbstractController
{
    /**
     * @var string
     */
    protected const PARAM_DESTINATION = 'to';

    public function indexAction(Request $request): RedirectResponse
    {
        $token = (string)$request->query->get(SearchSignalsConfig::CLICK_TOKEN_PARAM, '');
        $destination = (string)$request->query->get(static::PARAM_DESTINATION, '/');

        if (!$this->isSafeRelativeDestination($destination)) {
            $destination = '/';
        }

        $payload = $this->getFactory()->createClickTokenCodec()->decode($token);

        if ($payload !== null) {
            $this->getFactory()->getSearchSignalsClient()->publishClickEvent(
                $payload->query,
                $payload->abstractSku,
                $payload->rank,
                $payload->storeName,
                $payload->localeName,
            );
        }

        return new RedirectResponse($destination);
    }

    /**
     * Prevents this endpoint from being used as an open redirect: only a same-site, path-only
     * destination is allowed -- no scheme, no host, no protocol-relative `//host` form.
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
