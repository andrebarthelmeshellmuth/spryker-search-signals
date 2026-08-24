<!-- markdownlint-disable -->
# Search Signals

[![CI](https://github.com/andrebarthelmeshellmuth/spryker-search-signals/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/andrebarthelmeshellmuth/spryker-search-signals/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-%E2%89%A5%208.3-777bb4)](composer.json)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-2a6b2a)](phpstan.neon)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

## Contents

- [What does this do?](#what-does-this-do)
- [Status](#status)
- [Requirements](#requirements)
- [Installation](#installation)
- [Channel 1: batch import from S3](#channel-1-batch-import-from-s3)
- [Channel 2: local-data transformer plugins](#channel-2-local-data-transformer-plugins)
- [Channel 3a: SRP impression/click CTR (no personal data)](#channel-3a-srp-impressionclick-ctr-no-personal-data)
- [Channel 3b: cart-add/order counters](#channel-3b-cart-addorder-counters)
- [The Zed GUI](#the-zed-gui)
- [Console commands](#console-commands)
- [Configuration](#configuration)
- [Testing and CI](#testing-and-ci)
- [Limitations](#limitations)
- [License](#license)

## What does this do?

Generates the business-signal inputs [`spryker-community/search-ranking`](https://github.com/andrebarthelmeshellmuth/spryker-search-ranking)
consumes, from data the shop already has or already produces, instead of requiring an adopter to source
`top_seller`, `pdp_impressions` and friends externally. Everything converges on search-ranking's own
existing `search_ranking_product_metric.csv` contract (`abstract_sku, metric_name, raw_value, store,
locale`) — this package never adds a new seam to search-ranking, it just produces more rows for the
importer it already has.

Three independent producers, one shared sink:

- **Channel 1** — documentation + an example config for importing that CSV from S3 instead of the local
  filesystem. No new PHP.
- **Channel 2** — a project-overridable plugin stack that shapes data the shop already has locally (stock
  level, delivery time) into a metric, on a batch/cron cadence.
- **Channel 3a** — a GDPR-free SRP impression/click capture pipeline, emitting a Bayesian-shrunk
  click-through-rate metric. Zero personal data retained anywhere.
- **Channel 3b** — deliberately unattributed cart-add/order counters, reusing channel 3a's own
  capture/drain pipeline.

## Status

Channel 3a and 3b are code-complete and have been live-verified end to end against a real running
demoshop (a real SRP page, real clicks, real cart-adds, real drain/rollup/emit runs). Channels 1 and 2
are new: channel 1 is documentation-only by design; channel 2 ships two naive default plugins
(`StockLevelMetricTransformerPlugin`, `DeliveryTimeMetricTransformerPlugin`), neither registered by
default. The Zed "Overview" page and `search-signals:check-installation` are new and have not yet been
run against a real docker-compose stack — see [Testing and CI](#testing-and-ci).

**Not built**: the query-volume weight export to `spryker-community/search-ranking-optimizer` and the Zed
"Top Queries" view (both were sketched in the original design, deliberately deferred). Channel 3b's
richer, per-query-attributed variant (a client-side `localStorage` ledger) is also deferred — the counters
this package ships today are intentionally unattributed, same shape as search-ranking's own `top_seller`.

## Requirements

- PHP 8.3+
- A Spryker shop on `spryker/search-elasticsearch` ^1.10.0, `spryker/catalog` ^5.0.0, `spryker/queue`
  ^1.29.0 (the Storage-KV mechanism this package uses does not need a broker, but the package still
  depends on `spryker/queue-extension` for its plugin interfaces), `spryker/store` ^1.19.0
- `spryker/stock`, only if you register the shipped Channel 2 plugins (see [Channel
  2](#channel-2-local-data-transformer-plugins))
- `spryker/flysystem` + `spryker/flysystem-aws3v3-file-system`, only if you use Channel 1

## Installation

### 1. Install the package

```
composer require spryker-community/search-signals
```

### 2. Register the `SprykerCommunity` core namespace

In `config/Shared/config_default.php`:

```php
$config[KernelConstants::CORE_NAMESPACES] = array_merge(
    $config[KernelConstants::CORE_NAMESPACES] ?? [],
    ['SprykerCommunity'],
);
```

Already done if you have any other `spryker-community/*` package installed.

### 3. Generate transfers and build the schema

```
vendor/bin/console transfer:generate
vendor/bin/console propel:diff
vendor/bin/console propel:migrate
vendor/bin/console propel:model:build
```

Creates `spy_search_signals_impression_event`, `spy_search_signals_click_event`,
`spy_search_signals_product_ctr_daily` and `spy_search_signals_query_ctr_weekly`. Use `propel:diff` +
`propel:migrate`, never `propel:sql:insert` (that applies the shop's *entire* schema dump, not just this
package's new tables).

### 4. Register the Client-layer plugins

In `src/Pyz/Client/Catalog/CatalogDependencyProvider.php`:

```php
use SprykerCommunity\Client\SearchSignals\Plugin\Catalog\SearchSignalsImpressionResultFormatterPlugin;

protected function getResultFormatterPlugins(): array
{
    return [
        // ...your existing formatters
        new SearchSignalsImpressionResultFormatterPlugin(),
    ];
}
```

In `src/Pyz/Client/Cart/CartDependencyProvider.php` (secondary path — only fires for a guest/non-persistent
quote; see step 5 for the path that actually fires on a persistent-cart project):

```php
use SprykerCommunity\Client\SearchSignals\Plugin\Cart\SearchSignalsCartAddCartChangeRequestExpanderPlugin;

protected function getAddItemsRequestExpanderPlugins(): array
{
    return [
        new SearchSignalsCartAddCartChangeRequestExpanderPlugin(),
    ];
}
```

### 5. Register the Zed-layer plugins

In `src/Pyz/Zed/Cart/CartDependencyProvider.php` — the path that actually fires when
`PersistentCartFeature` is active (persistent-cart add-to-cart requests are proxied straight to Zed and
never reach the Client-side expander stack):

```php
use SprykerCommunity\Zed\SearchSignals\Communication\Plugin\Cart\SearchSignalsCartItemExpanderPlugin;

protected function getExpanderPlugins(): array
{
    return [
        new SearchSignalsCartItemExpanderPlugin(),
    ];
}
```

In `src/Pyz/Zed/Sales/SalesDependencyProvider.php`:

```php
use SprykerCommunity\Zed\SearchSignals\Communication\Plugin\Sales\SearchSignalsOrderPostSavePlugin;

protected function getOrderPostSavePlugins(): array
{
    return [
        new SearchSignalsOrderPostSavePlugin(),
    ];
}
```

### 6. Register the Yves-layer plugins

In `src/Pyz/Yves/Router/RouterDependencyProvider.php`:

```php
use SprykerCommunity\Yves\SearchSignalsWidget\Plugin\Router\SearchSignalsWidgetRouteProviderPlugin;

$routeProviderPlugins[] = new SearchSignalsWidgetRouteProviderPlugin();
```

In `src/Pyz/Yves/Twig/TwigDependencyProvider.php`:

```php
use SprykerCommunity\Yves\SearchSignalsWidget\Plugin\Twig\SearchSignalsWidgetTwigPlugin;

$twigPlugins[] = new SearchSignalsWidgetTwigPlugin();
```

Only needed if you plan to reach `/search-signals-widget/check-installation` (see [step
10](#10-verify-the-installation)): register `SeeSearchSignalsCheckInstallationPermissionPlugin` in both
`src/Pyz/Client/Permission/PermissionDependencyProvider.php` and
`src/Pyz/Zed/Permission/PermissionDependencyProvider.php`, same two-file registration every sibling
package's own check-installation permission needs:

```php
use SprykerCommunity\Shared\SearchSignals\Plugin\SeeSearchSignalsCheckInstallationPermissionPlugin;

new SeeSearchSignalsCheckInstallationPermissionPlugin(),
```

Then wire the click-tracking molecule into your SRP product-grid template — one call to
`searchSignalsClickUrl(destinationUrl, query, abstractSku, rank)` per rendered product, and a
`<search-signals-click-tracker data-click-url="...">` element next to it (see
`page-layout-catalog.twig`'s own real wiring in this project's `src/Pyz/Yves/CatalogPage/Theme/default/templates/page-layout-catalog/page-layout-catalog.twig`
for a worked example), then rebuild the frontend:

```
yarn yves
```

### 7. Register the consoles

In `src/Pyz/Zed/Console/ConsoleDependencyProvider.php`:

```php
use SprykerCommunity\Zed\SearchSignals\Communication\Console\SearchSignalsCheckInstallationConsole;
use SprykerCommunity\Zed\SearchSignals\Communication\Console\SearchSignalsDrainQueueConsole;
use SprykerCommunity\Zed\SearchSignals\Communication\Console\SearchSignalsEmitConsole;
use SprykerCommunity\Zed\SearchSignals\Communication\Console\SearchSignalsRollupConsole;
use SprykerCommunity\Zed\SearchSignals\Communication\Console\SearchSignalsTransformLocalMetricsConsole;

$commands[] = new SearchSignalsDrainQueueConsole();
$commands[] = new SearchSignalsRollupConsole();
$commands[] = new SearchSignalsEmitConsole();
$commands[] = new SearchSignalsTransformLocalMetricsConsole();
$commands[] = new SearchSignalsCheckInstallationConsole();
```

Schedule `drain-queue`, `rollup`, and `emit` on a cron, same cadence as search-ranking's own
`normalize`/`randomize` commands — see [Console commands](#console-commands).

### 8. Set the click-token secret

```
SEARCH_SIGNALS_CLICK_TOKEN_SECRET=<64 random hex chars>
```

Both the Yves side (signs the token) and the Zed side (nothing verifies it — see [Channel
3a](#channel-3a-srp-impressionclick-ctr-no-personal-data)) fall back to the empty string via `getenv()`
returning `false` when unset. That is fine for local development, but makes every click token trivially
forgeable — set a real value before a real deploy.

### 9. Register the Zed navigation entry

Copy this package's own `Communication/navigation.xml` wrapper entry (`<search-signals-gui>`) into your
project's `config/Zed/navigation.xml`, then rebuild the nav cache:

```
vendor/bin/console navigation:build-cache
```

### 10. Verify the installation

```
vendor/bin/console search-signals:check-installation
```

Checks the core namespace, that the Zed plugin classes are loadable, that the click-token secret is set,
that the Storage-KV backend is reachable, and that the schema is installed. Complementary Yves-side check
(only reachable once you opt in — see [Configuration](#configuration)):

```
/search-signals-widget/check-installation
```

## Channel 1: batch import from S3

Cheapest of the four channels — effectively zero new package code, and pure Spryker-core capability. The
`search_ranking_product_metric.csv` contract search-ranking already imports doesn't care where its bytes
came from; only which named filesystem service the DataImport action's `file_system` key points at does.

1. `composer require spryker/flysystem spryker/flysystem-aws3v3-file-system` (if not already installed).
2. Declare an S3 filesystem service in `config/Shared/config_default.php` (this project already has one,
   named `s3-import` — key/secret/bucket/region, override per-environment, e.g.
   `config_default-production.php`):
   ```php
   $config[FileSystemConstants::FILESYSTEM_SERVICE]['s3-import'] = [
       'sprykerAdapterClass' => Aws3v3FilesystemBuilderPlugin::class,
       'path' => '/',
       'key' => getenv('AWS_ACCESS_KEY_ID') ?: '',
       'secret' => getenv('AWS_SECRET_ACCESS_KEY') ?: '',
       'bucket' => getenv('SEARCH_SIGNALS_S3_BUCKET') ?: '',
       'region' => getenv('AWS_REGION') ?: 'eu-central-1',
   ];
   ```
3. Point a `search-ranking-product-metric` DataImport action at it with `file_system: s3-import` instead
   of a local `source` path — see `data/import/production/s3_search_signals.yml` in this project for a
   complete, runnable example.

Getting the CSV **into** the bucket in the first place is a separate, unrelated concern (a cron-driven
export/sync job) — out of scope for this package, same as today's "bring your own CSV" for the local path.

## Channel 2: local-data transformer plugins

`SearchSignalsMetricTransformerPluginInterface` (`Zed\SearchSignals\Dependency\Plugin`) — one plugin per
metric, each returning `array<abstractSku, rawValue>` for a given store. This package ships two NAIVE
DEFAULTS, neither registered unless you opt in:

- `StockLevelMetricTransformerPlugin` — raw on-hand stock, summed across every warehouse available to the
  store, via `spryker/stock`'s own `StockFacadeInterface::calculateProductAbstractStockForStore()`.
- `DeliveryTimeMetricTransformerPlugin` — a genuine stub: Spryker core has no "lead time per warehouse"
  concept out of the box, so this returns the same configured flat value for every in-stock product. Copy
  it into your project and replace the placeholder with a real per-product/per-warehouse computation (the
  original design's own worked example: "soonest available warehouse's lead time").

Register the stack in your own project-level `SearchSignalsDependencyProvider` override:

```php
namespace Pyz\Zed\SearchSignals;

use SprykerCommunity\Zed\SearchSignals\Communication\Plugin\MetricTransformer\DeliveryTimeMetricTransformerPlugin;
use SprykerCommunity\Zed\SearchSignals\Communication\Plugin\MetricTransformer\StockLevelMetricTransformerPlugin;
use SprykerCommunity\Zed\SearchSignals\SearchSignalsDependencyProvider as SprykerSearchSignalsDependencyProvider;

class SearchSignalsDependencyProvider extends SprykerSearchSignalsDependencyProvider
{
    protected function getMetricTransformerPlugins(): array
    {
        return [
            new StockLevelMetricTransformerPlugin(),
            new DeliveryTimeMetricTransformerPlugin(),
        ];
    }
}
```

Then run (or cron) `search-signals:transform-local-metrics <store>` — batch, on the same cadence as
search-ranking's own `normalize`, never computed at search-request time. Writes to its own CSV, separate
from channel 3a/3b's output file, so the two independently-scheduled commands never clobber each other.

## Channel 3a: SRP impression/click CTR (no personal data)

Two server-side events only, both zero-personal-data by design:

- **Impression**: recorded at SRP render — `store, locale, query, sku, rank`. Nothing visitor-specific.
- **Click**: resolved from a stateless, signed context token on the SRP→PDP link (an HMAC over `query,
  abstractSku, rank, timestamp` — see `ClickTokenCodec`), not a session lookup. No session ID, no customer
  ID, no IP is ever retained.

Both are published to a **Storage-KV poor-man's queue** (`Spryker\Client\Storage`, the same client Yves
already uses for Product/Category/CMS reads) rather than RabbitMQ — this project deliberately never gives
Yves broker credentials (see the plan's own architecture note). One Storage key per event
(`search_signals:impression:{uniqid}` / `search_signals:click:{uniqid}`), drained by
`search-signals:drain-queue` into the raw event tables.

`search-signals:rollup` then folds raw events into append-only, never-overwritten daily (product-scoped)
and weekly (query-scoped) buckets, and prunes raw events past `getRawEventRetentionDays()`.
`search-signals:emit` sums each product's rollup counts over `getEmitWindowDays()`, shrinks the resulting
CTR via Bayesian shrinkage (`(clicks + α·prior) / (impressions + α)`, catalogue-wide mean CTR as the prior,
`α` from `getShrinkageAlpha()`), and writes the `ctr` metric row.

No position-bias correction in v1 — CTR alone is a weaker relevance proxy than conversion and more exposed
to rank position than a deeper-funnel event. Emitted uncorrected, behind this documented caveat; everything
the correction needs is logged from day one for a later v1.1 pass.

## Channel 3b: cart-add/order counters

Deliberately unattributed to any search term — same shape as search-ranking's own `top_seller` metric, not
a lesser version of a search signal. Two Zed-side hooks, both reusing the same `ProductCounterIncrementer`
(which resolves the full store config, including locales, via a Zed Store-facade bridge rather than
trusting whatever `StoreTransfer` arrived over the wire from Yves — that transfer only ever carries id +
name):

- `SearchSignalsCartItemExpanderPlugin` (`ItemExpanderPluginInterface`) — the path that actually fires on
  this project (`PersistentCartFeature` proxies add-to-cart straight to Zed).
- `SearchSignalsOrderPostSavePlugin` (`OrderPostSavePluginInterface`) — fires on order persistence, already
  in Zed, so it calls the Facade directly with no Storage-KV relay needed.

Emitted as two more metric rows (`cart_add`, `order`) alongside `ctr` by the same `emit` console command,
no shrinkage applied (raw counts, same as `top_seller`).

## The Zed GUI

One page, `Communication/Controller/IndexController` (`/search-signals/index`, nav entry under "Search
Toolbox"): a per store+locale coverage table — distinct product count, total impressions/clicks/CTR,
cart-adds, orders, all-time. A plain server-rendered table, not a SprykerTable widget: the row count is
bounded by how many store+locale combinations a shop has, not by data volume, so the AJAX/paging machinery
a SprykerTable brings would be pure overhead. Read-only — every action still lives in the console commands
above; this page is for visibility, not control.

## Console commands

| Command | What it does |
| --- | --- |
| `search-signals:drain-queue` | Drains the Storage-KV queue into the raw impression/click event tables. |
| `search-signals:rollup` | Folds raw events into the daily/weekly buckets, prunes old raw events. |
| `search-signals:emit <store> <locale>` | Writes the `ctr`/`cart_add`/`order` metric rows to CSV. |
| `search-signals:transform-local-metrics <store>` | Runs channel 2's registered plugins, writes their CSV. |
| `search-signals:check-installation` | Diagnoses the Zed-side half of the installation. |

## Configuration

- `SEARCH_SIGNALS_CLICK_TOKEN_SECRET` — see [step 8](#8-set-the-click-token-secret).
- `SearchSignalsConfig::getRawEventRetentionDays()` — default 90.
- `SearchSignalsConfig::getEmitWindowDays()` — default 90 (flat window; exponential decay is a deferred
  v1.1 item).
- `SearchSignalsConfig::getShrinkageAlpha()` / `getShrinkagePrior()` — Bayesian-shrinkage parameters, see
  [Channel 3a](#channel-3a-srp-impressionclick-ctr-no-personal-data).
- `SearchSignalsConstants::IS_CHECK_INSTALLATION_PAGE_ENABLED` — defaults to disabled; set `true` in a
  development-tier config to reach `/search-signals-widget/check-installation`. Also gated by
  `SeeSearchSignalsCheckInstallationPermissionPlugin` — see [step 6](#6-register-the-yves-layer-plugins).

## Testing and CI

- **Portable** (`@group Portable`, `composer test-portable`) — 59 tests, live-verified green: full unit
  coverage of every pure/mockable Business and Yves class (`ClickTokenCodec`, `BayesianShrinkageCalculator`,
  `ClickUrlBuilder`, `RollupBuilder`, `ProductMetricCsvWriter`, `QueueDrainer`,
  `ImpressionEventWriter`/`ClickEventWriter`, `ProductCounterIncrementer`, `MetricCoverageReader`,
  `LocalMetricCsvWriter`), the Facade's full delegation surface, and the three
  `SearchSignalsCheckInstallationConsole` checks that don't need Zed DI (core namespace, plugin classes,
  click-token secret). No Locator/DB/search engine. Runs standalone, including in CI with no host shop.
- **Not Portable-testable, verified by hand instead**: `SearchSignalsCheckInstallationConsole`'s other two
  checks (`checkStorageKv`/`checkSchema`, both need `getFactory()` against a real Zed container) and
  `CheckInstallationController` (Yves, needs `getTwig()`/`getRouter()`) — both live-verified via
  `console search-signals:check-installation` and a real browser session, not by an automated test.
- **Zed suite** (`tests/SprykerCommunityTest/Zed/SearchSignals`) — scaffolded (codeception.yml + Tester),
  no Repository/EntityManager integration tests written yet; those need a real database and are the next
  thing to add here.
- **Presentation suite** (`tests/SprykerCommunityTest/Zed/SearchSignalsGuiPresentation`) — `OverviewCest`
  (2 tests), live-verified green via `docker/sdk testing` + WebDriver, run from the demoshop root (see the
  Cest's own docblock for the exact command — it needs a project-level test helper this package's own
  standalone `vendor/bin/codecept` doesn't have).
- `composer phpstan` (host shop) vs. `composer phpstan-ci` (standalone).
- `composer check-floors` — catches undeclared dependencies exactly like the ones this package shipped
  with initially (`spryker/cart-extension`, `spryker/permission-extension`, `spryker/router` were used in
  `src/` but missing from `require`, masked by the demoshop happening to have them installed transitively).
  Run it after adding any new `use` of a Spryker core class.

```
composer validate --no-check-publish
vendor/bin/phpcs
vendor/bin/phpmd src text phpmd.xml
vendor/bin/phpmd src text phpmd-public-methods.xml
vendor/bin/phpmd src text phpmd-parameter-list.xml
composer rector-dry-run
composer check-floors
composer test-portable
```

## Limitations

- No position-bias correction yet (channel 3a, v1.1 item).
- Channel 3b is deliberately unattributed — no per-query credit for a cart-add/order. A GDPR-safe,
  client-side `localStorage`-ledger design for richer attribution was sketched but not built (deferred, not
  rejected).
- No query-volume weight export to `search-ranking-optimizer` and no Zed "Top Queries" view yet (both were
  part of the original design, deliberately deferred).
- Channel 2's two shipped plugins reach a sibling module's Facade via `Spryker\Zed\Kernel\Locator` directly
  rather than constructor injection (these plugins are consumed by, not constructed by, this module's own
  Business layer) — the same idiom Zed console commands already use for the same reason.

## License

MIT, see [LICENSE](LICENSE).
