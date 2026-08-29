<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchSignals\Plugin\Catalog;

use Elastica\ResultSet;
use Generated\Shared\Search\PageIndexMap;
use Spryker\Client\SearchElasticsearch\Plugin\ResultFormatter\AbstractElasticsearchResultFormatterPlugin;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig;

/**
 * Captures one impression event per SRP result at render time -- (query, sku, rank, store, locale) only,
 * nothing about the visitor. See the search-signals plan's channel 3a. Registered same as any other
 * `ResultFormatterPlugin` in the project's `CatalogDependencyProvider`.
 *
 * @method \SprykerCommunity\Client\SearchSignals\SearchSignalsFactory getFactory()
 */
class SearchSignalsImpressionResultFormatterPlugin extends AbstractElasticsearchResultFormatterPlugin
{
    /**
     * @var string
     */
    public const NAME = 'SearchSignalsImpression';

    /**
     * Confirmed live against the real index (2026-08-23): `search-result-data` carries `abstract_sku`
     * directly, so no id->sku resolution is needed the way {@see \SprykerCommunity\Client\SearchDebug\Plugin\Catalog\SearchDebugResultFormatterPlugin}
     * needs for `id_product_abstract` (a different, internal debug-data key).
     *
     * @var string
     */
    protected const ABSTRACT_SKU_KEY = 'abstract_sku';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     * - Always returns `[]` -- this plugin's job is the side effect (publishing impression events), not
     *   contributing anything to the formatted search result payload.
     *
     * @param \Elastica\ResultSet $searchResult
     * @param array<string, mixed> $requestParameters
     *
     * @return array<string, mixed>
     */
    protected function formatSearchResult(ResultSet $searchResult, array $requestParameters): array
    {
        $query = (string)($requestParameters[SearchSignalsConfig::REQUEST_PARAM_SEARCH_STRING] ?? '');

        if ($query === '') {
            return [];
        }

        $abstractSkusByRank = $this->getAbstractSkusByRank($searchResult);

        if ($abstractSkusByRank === []) {
            return [];
        }

        $this->getFactory()->createImpressionEventPublisher()->publish(
            $query,
            $abstractSkusByRank,
            $this->getFactory()->getStoreClient()->getCurrentStore()->getNameOrFail(),
            $this->getFactory()->getLocaleClient()->getCurrentLocale(),
        );

        return [];
    }

    /**
     * @param \Elastica\ResultSet $searchResult
     *
     * @return array<int, string>
     */
    protected function getAbstractSkusByRank(ResultSet $searchResult): array
    {
        $abstractSkusByRank = [];

        foreach (array_values($searchResult->getResults()) as $rank => $document) {
            $source = $document->getSource();

            if (!isset($source[PageIndexMap::SEARCH_RESULT_DATA][static::ABSTRACT_SKU_KEY])) {
                continue;
            }

            $abstractSkusByRank[$rank] = (string)$source[PageIndexMap::SEARCH_RESULT_DATA][static::ABSTRACT_SKU_KEY];
        }

        return $abstractSkusByRank;
    }
}
