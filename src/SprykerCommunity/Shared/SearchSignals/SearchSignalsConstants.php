<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Shared\SearchSignals;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface SearchSignalsConstants
{
    /**
     * Specification:
     * - Toggles whether the `search-signals-widget/check-installation` Yves diagnostic page's route
     *   registers at all.
     * - Defaults to **disabled**: the route does not exist anywhere unless a project opts in. Fail-closed
     *   by default, matching the identical flag on `spryker-community/search-debug` and
     *   `spryker-community/search-ranking-optimizer`, and Spryker core's own idiom for a dev diagnostic
     *   (`Spryker\Shared\WebProfiler\WebProfilerConstants::IS_WEB_PROFILER_ENABLED`).
     * - Also gated by {@see \SprykerCommunity\Shared\SearchSignals\Plugin\SeeSearchSignalsCheckInstallationPermissionPlugin},
     *   same two-gate shape as every sibling package's own check-installation page, even though this
     *   page itself carries no per-customer data — it reports structural facts (is the Twig function
     *   registered, is the click route registered), the same category of information
     *   {@see \SprykerCommunity\Zed\SearchSignals\Communication\Console\SearchSignalsCheckInstallationConsole}
     *   already reports for the Zed half. This route flag is still the FIRST gate: it keeps a diagnostic
     *   URL from existing in production by default, before the permission check ever runs.
     * - Set to `true` in a project's development-tier config (e.g. `config_default-development.php`) to
     *   opt in.
     *
     * @api
     *
     * @var string
     */
    public const IS_CHECK_INSTALLATION_PAGE_ENABLED = 'SEARCH_SIGNALS:IS_CHECK_INSTALLATION_PAGE_ENABLED';
}
