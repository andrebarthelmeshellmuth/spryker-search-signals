<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchSignalsWidget\Plugin\Twig;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\TwigExtension\Dependency\Plugin\TwigPluginInterface;
use Spryker\Yves\Kernel\AbstractPlugin;
use Twig\Environment;
use Twig\TwigFunction;

/**
 * @method \SprykerCommunity\Yves\SearchSignalsWidget\SearchSignalsWidgetFactory getFactory()
 */
class SearchSignalsWidgetTwigPlugin extends AbstractPlugin implements TwigPluginInterface
{
    /**
     * @var string
     */
    public const FUNCTION_NAME_CLICK_URL = 'searchSignalsClickUrl';

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter $container is mandated by TwigPluginInterface.
     *
     * @param \Twig\Environment $twig
     * @param \Spryker\Service\Container\ContainerInterface $container
     */
    public function extend(Environment $twig, ContainerInterface $container): Environment
    {
        $twig->addFunction(new TwigFunction(
            static::FUNCTION_NAME_CLICK_URL,
            fn (string $destinationUrl, string $query, string $abstractSku, int $rank): string => $this->getFactory()
                ->createClickUrlBuilder()
                ->build($destinationUrl, $query, $abstractSku, $rank),
        ));

        return $twig;
    }
}
