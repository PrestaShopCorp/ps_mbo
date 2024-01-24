<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
declare(strict_types=1);

namespace PrestaShop\Module\Mbo\Addons\Provider;

use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\Foundation\Version;
use PrestaShopBundle\Service\DataProvider\Admin\CategoriesProvider;
use stdClass;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;

class LinksProvider
{
    public const DEFAULT_LANGUAGE = 'en';

    /**
     * @var Version
     */
    protected $version;

    /**
     * @var LegacyContext
     */
    protected $context;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var CategoriesProvider
     */
    private $categoriesProvider;

    /**
     * @var Router
     */
    private $router;

    /**
     * @param Version $version
     * @param LegacyContext $context
     * @param Configuration $configuration
     * @param RequestStack $requestStack
     * @param TranslatorInterface $trans
     */
    public function __construct(
        Version $version,
        LegacyContext $context,
        Configuration $configuration,
        RequestStack $requestStack,
        CategoriesProvider $categoriesProvider,
        TranslatorInterface $trans,
        Router $router
    ) {
        $this->version = $version;
        $this->context = $context;
        $this->configuration = $configuration;
        $this->requestStack = $requestStack;
        $this->categoriesProvider = $categoriesProvider;
        $this->translator = $trans;
        $this->router = $router;
    }

    /**
     * We cannot use http_build_query() here due to a bug on Addons
     *
     * @see https://github.com/PrestaShop/PrestaShop/pull/9255/files#r200498010
     *
     * @return string
     */
    public function getSelectionLink(): string
    {
        $link = 'https://addons.prestashop.com/iframe/search-1.7.php?psVersion=' . $this->version->getSemVersion()
            . '&isoLang=' . $this->context->getContext()->language->iso_code
            . '&isoCurrency=' . $this->context->getContext()->currency->iso_code
            . '&isoCountry=' . $this->context->getContext()->country->iso_code
            . '&activity=' . $this->configuration->getInt('PS_SHOP_ACTIVITY')
            . '&parentUrl=' . $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();

        if ('AdminPsMboTheme' === $this->requestStack->getCurrentRequest()->attributes->get('_legacy_controller')) {
            $link .= '&onlyThemes=1';
        }

        return $link;
    }

    public function getCategoryLink(string $categoryName): string
    {
        $category = $this->getCategoryByName($categoryName);

        $routeParams = [];
        if ($category && 'other' !== mb_strtolower($categoryName)) {
            $routeParams['filterCategoryRef'] = $category->refMenu;
            $routeParams['mbo_cdc_path'] = '/#/modules';
        }

        return $this->router->generate('admin_mbo_catalog_module', $routeParams);
    }

    /**
     * Returns a category object based on its name.
     *
     * @param string $categoryName
     *
     * @return stdClass|null
     */
    private function getCategoryByName(string $categoryName): ?stdClass
    {
        foreach ($this->categoriesProvider->getCategories() as $parentCategory) {
            foreach ($parentCategory->subMenu as $childCategory) {
                if ($childCategory->name === $categoryName) {
                    return $childCategory;
                }
            }
        }

        return null;
    }
}
