<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchSignalsWidget\Controller;

use Spryker\Yves\Kernel\Controller\AbstractController;
use Spryker\Yves\Kernel\PermissionAwareTrait;
use SprykerCommunity\Shared\SearchSignals\Plugin\SeeSearchSignalsCheckInstallationPermissionPlugin;
use SprykerCommunity\Yves\SearchSignalsWidget\Plugin\Twig\SearchSignalsWidgetTwigPlugin;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Twig\Error\SyntaxError;

/**
 * Diagnoses the Yves-side half of a search-signals installation — the half
 * {@see \SprykerCommunity\Zed\SearchSignals\Communication\Console\SearchSignalsCheckInstallationConsole}
 * cannot reach, because Zed never bootstraps the Yves DI container. Complementary to that console
 * command, not a replacement: this page does not re-check the Storage-KV backend, the schema, or the
 * click-token secret — run the console command for those.
 *
 * Deliberately covers the failure mode that produces a page that *looks* installed: the click-tracking
 * Twig function registered but the SRP template never calling it (products render with plain, untracked
 * PDP links), or the click-redirect route missing (every click-tracker element would rewrite links to a
 * 404). Neither raises an error anywhere — see README, "Testing and CI" for the one thing this page
 * still cannot confirm: whether the frontend bundle (`click-tracker` molecule) was actually rebuilt.
 *
 * Reachable only when BOTH gates pass: the route itself only exists when
 * {@see \SprykerCommunity\Shared\SearchSignals\SearchSignalsConstants::IS_CHECK_INSTALLATION_PAGE_ENABLED}
 * allows it (defaults to `false`), AND the visiting customer holds
 * {@see SeeSearchSignalsCheckInstallationPermissionPlugin} — same two-gate shape as every sibling
 * package's own check-installation page, even though this page itself carries no per-customer data.
 * Missing the permission where the route does exist renders a dedicated explanation with the exact
 * remedy, rather than a bare 403 — almost always someone mid-setup, not an intrusion.
 *
 * @method \SprykerCommunity\Yves\SearchSignalsWidget\SearchSignalsWidgetFactory getFactory()
 */
class CheckInstallationController extends AbstractController
{
    use PermissionAwareTrait;

    /**
     * @return \Spryker\Yves\Kernel\View\View|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        if (!$this->can(SeeSearchSignalsCheckInstallationPermissionPlugin::KEY)) {
            return $this->renderView(
                '@SearchSignalsWidget/views/check-installation/permission-denied.twig',
                [],
                new Response('', Response::HTTP_FORBIDDEN),
            );
        }

        return $this->view(
            [
                'checks' => $this->runChecks(),
            ],
            [],
            '@SearchSignalsWidget/views/check-installation/check-installation.twig',
        );
    }

    /**
     * @return array<int, array{label: string, passed: bool, remedy: string|null}>
     */
    protected function runChecks(): array
    {
        return [
            $this->checkTwigFunction(),
            $this->checkClickRoute(),
        ];
    }

    /**
     * @return array{label: string, passed: bool, remedy: string|null}
     */
    protected function checkTwigFunction(): array
    {
        $isRegistered = $this->isTwigFunctionCallable(SearchSignalsWidgetTwigPlugin::FUNCTION_NAME_CLICK_URL);

        return [
            'label' => 'Twig helper function "searchSignalsClickUrl" is registered',
            'passed' => $isRegistered,
            'remedy' => $isRegistered
                ? null
                : 'Register SearchSignalsWidgetTwigPlugin in src/Pyz/Yves/Twig/TwigDependencyProvider.php (see README, "Installation").',
        ];
    }

    /**
     * Compiles a throwaway one-line template that calls the function, rather than inspecting
     * `Twig\Environment`'s function registry directly — that registry is only reachable through
     * `getFunction()`, which Twig marks `@internal`. `createTemplate()` is Twig's own documented,
     * non-internal way to ask "does this compile", and already throws {@see SyntaxError} for an unknown
     * function at compile time, so no render is needed either.
     *
     * @param string $functionName
     */
    protected function isTwigFunctionCallable(string $functionName): bool
    {
        try {
            $this->getTwig()->createTemplate(sprintf('{{ %s("/", "office chair", "ABC-123", 0) }}', $functionName));

            return true;
        } catch (SyntaxError) {
            return false;
        }
    }

    /**
     * @return array{label: string, passed: bool, remedy: string|null}
     */
    protected function checkClickRoute(): array
    {
        $isRegistered = $this->isRouteRegistered('search-signals/click');

        return [
            'label' => 'Click-redirect route ("search-signals/click") is registered',
            'passed' => $isRegistered,
            'remedy' => $isRegistered
                ? null
                : 'Register SearchSignalsWidgetRouteProviderPlugin in src/Pyz/Yves/Router/RouterDependencyProvider.php (see README, "Installation").',
        ];
    }

    /**
     * @param string $routeName
     */
    protected function isRouteRegistered(string $routeName): bool
    {
        try {
            $this->getRouter()->generate($routeName);

            return true;
        } catch (RouteNotFoundException) {
            return false;
        }
    }
}
