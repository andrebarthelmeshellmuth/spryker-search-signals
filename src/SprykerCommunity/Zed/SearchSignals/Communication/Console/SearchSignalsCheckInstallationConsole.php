<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Communication\Console;

use Spryker\Shared\Config\Config;
use Spryker\Shared\Kernel\KernelConstants;
use Spryker\Zed\Kernel\Communication\Console\Console;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig;
use SprykerCommunity\Zed\SearchSignals\Communication\Plugin\Cart\SearchSignalsCartItemExpanderPlugin;
use SprykerCommunity\Zed\SearchSignals\Communication\Plugin\Sales\SearchSignalsOrderPostSavePlugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Diagnoses a search-signals installation. Most of this package's wiring fails SILENTLY when missed — a
 * missing plugin registration just means events never get captured, with nothing in any log to say why.
 *
 * Deliberately honest about its own limits: it runs in Zed, so it cannot introspect the Yves DI container
 * or confirm the click-tracking Twig/JS wiring actually renders on a real SRP page (see README, "Testing
 * and CI" for the manual browser check that closes that gap).
 *
 * @method \SprykerCommunity\Zed\SearchSignals\Communication\SearchSignalsCommunicationFactory getFactory()
 */
class SearchSignalsCheckInstallationConsole extends Console
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'search-signals:check-installation';

    /**
     * @var string
     */
    public const COMMAND_DESCRIPTION = 'Diagnoses a search-signals installation: core namespace, plugin classes, click-token secret, Storage-KV reachability, and schema install.';

    /**
     * @var string
     */
    protected const CORE_NAMESPACE = 'SprykerCommunity';

    /**
     * @var array<string>
     */
    protected array $failures = [];

    /**
     * @var array<string>
     */
    protected array $warnings = [];

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME);
        $this->setDescription(static::COMMAND_DESCRIPTION);

        parent::configure();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter $input is mandated by the Console base class.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->checkCoreNamespace($output);
        $this->checkPluginClasses($output);
        $this->checkClickTokenSecret($output);
        $this->checkStorageKv($output);
        $this->checkSchema($output);

        $output->writeln('');

        foreach ($this->warnings as $warning) {
            $output->writeln(sprintf('<comment>! %s</comment>', $warning));
        }

        if ($this->failures !== []) {
            foreach ($this->failures as $failure) {
                $output->writeln(sprintf('<error>✗ %s</error>', $failure));
            }

            return static::CODE_ERROR;
        }

        $output->writeln('<info>Everything checkable from the CLI is in place.</info>');
        $output->writeln('Not verifiable from Zed — Zed never bootstraps the Yves DI container, so it cannot confirm:');
        $output->writeln('  - Yves plugin registration (Twig + Router dependency providers)');
        $output->writeln('  - the click-tracking Twig/JS wiring actually rewrites PDP links on a rendered SRP');
        $output->writeln('  - the compiled frontend assets (click-tracker molecule) are built');
        $output->writeln('');
        $output->writeln('These need a real page load — see README, "Testing and CI", for the manual browser check.');

        return static::CODE_SUCCESS;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkCoreNamespace(OutputInterface $output): void
    {
        $coreNamespaces = Config::get(KernelConstants::CORE_NAMESPACES, []);

        if (in_array(static::CORE_NAMESPACE, $coreNamespaces, true)) {
            $output->writeln(sprintf('<info>✓</info> core namespace "%s" is registered', static::CORE_NAMESPACE));

            return;
        }

        $this->failures[] = sprintf(
            'Core namespace "%s" is NOT registered. Add it to KernelConstants::CORE_NAMESPACES in config/Shared/config_default.php.',
            static::CORE_NAMESPACE,
        );
    }

    /**
     * Class existence only — whether a project actually REGISTERED these plugins in its own
     * DependencyProviders is not visible from Zed. A missing class means a broken install; a present
     * class means "nothing is stopping you from registering it".
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkPluginClasses(OutputInterface $output): void
    {
        $requiredClasses = [
            'cart-item-expander plugin (register in Pyz\Zed\Cart\CartDependencyProvider::getExpanderPlugins())' => SearchSignalsCartItemExpanderPlugin::class,
            'order-post-save plugin (register in Pyz\Zed\Sales\SalesDependencyProvider::getOrderPostSavePlugins())' => SearchSignalsOrderPostSavePlugin::class,
        ];

        foreach ($requiredClasses as $label => $className) {
            if (class_exists($className)) {
                $output->writeln(sprintf('<info>✓</info> %s class is loadable', $label));

                continue;
            }

            $this->failures[] = sprintf('The %s (%s) could not be autoloaded.', $label, $className);
        }
    }

    /**
     * A genuinely-unset secret makes every click token trivially forgeable (both sides fall back to the
     * empty string via `getenv()` returning `false`) — not a hard failure (the package still functions
     * for local development against the empty-string fallback, as this package's own build session did),
     * but worth flagging loudly since it is easy to forget before a real deploy.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkClickTokenSecret(OutputInterface $output): void
    {
        $secret = getenv('SEARCH_SIGNALS_CLICK_TOKEN_SECRET');

        if ($secret !== false && $secret !== '') {
            $output->writeln('<info>✓</info> SEARCH_SIGNALS_CLICK_TOKEN_SECRET is set');

            return;
        }

        $this->warnings[] = 'SEARCH_SIGNALS_CLICK_TOKEN_SECRET is not set — click tokens fall back to an empty-string secret, which is fine for local development but must be set to a real random value before a real deploy (see README, "Configuration").';
    }

    /**
     * Read-only reachability check via the same Storage client the drain-queue console already uses in
     * production — a bounded `scanKeys()` call, never a write.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkStorageKv(OutputInterface $output): void
    {
        try {
            $scanResult = $this->getFactory()->getStorageClient()->scanKeys(
                SearchSignalsConfig::STORAGE_KEY_PREFIX_IMPRESSION_EVENT . '*',
                1,
                0,
            );
            $pendingCount = count($scanResult->getKeys());
        } catch (Throwable $exception) {
            $this->failures[] = sprintf('Storage-KV backend is not reachable: %s', $exception->getMessage());

            return;
        }

        $output->writeln(sprintf('<info>✓</info> Storage-KV backend reachable (%d pending impression key(s) sampled)', $pendingCount));
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkSchema(OutputInterface $output): void
    {
        $queryClass = 'Orm\\Zed\\SearchSignals\\Persistence\\SpySearchSignalsImpressionEventQuery';

        if (!class_exists($queryClass)) {
            $this->failures[] = 'Orm\Zed\SearchSignals\Persistence\SpySearchSignalsImpressionEventQuery does not exist — run `console propel:install` (or propel:diff + propel:migrate) after installing this package so its schema.xml is picked up.';

            return;
        }

        try {
            /** @var \Propel\Runtime\ActiveQuery\ModelCriteria $query */
            $query = $queryClass::create();
            $query->count();
        } catch (Throwable $exception) {
            $this->failures[] = sprintf('spy_search_signals_impression_event table is not reachable: %s', $exception->getMessage());

            return;
        }

        $output->writeln('<info>✓</info> database schema is installed and reachable');
    }
}
