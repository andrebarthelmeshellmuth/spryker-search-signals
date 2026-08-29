<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Shared\SearchSignals\Plugin;

use Spryker\Shared\PermissionExtension\Dependency\Plugin\PermissionPluginInterface;

/**
 * Gates {@see \SprykerCommunity\Yves\SearchSignalsWidget\Controller\CheckInstallationController} —
 * added for consistency with every sibling package's own check-installation page (search-debug,
 * search-ranking-optimizer, search-analyzer-config), even though this page itself carries no
 * per-customer data. The route-flag gate alone (`SearchSignalsConstants::IS_CHECK_INSTALLATION_PAGE_ENABLED`)
 * still does the actual work of keeping the URL out of production by default; this permission is the
 * second, defense-in-depth gate every sibling already has.
 *
 * For Zed & Client PermissionDependencyProvider::getPermissionPlugins() registration
 */
class SeeSearchSignalsCheckInstallationPermissionPlugin implements PermissionPluginInterface
{
    /**
     * @var string
     */
    public const KEY = 'SeeSearchSignalsCheckInstallationPermissionPlugin';

    public function getKey(): string
    {
        return static::KEY;
    }
}
