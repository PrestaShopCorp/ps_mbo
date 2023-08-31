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

namespace PrestaShop\Module\Mbo\Traits\Hooks;

use PrestaShop\PrestaShop\Core\Search\SearchPanel;
use Symfony\Component\Routing\Router;

trait UseActionGetAlternativeSearchPanels
{
    /**
     * Hook actionGetAlternativeSearchPanels.
     *
     * Retrieve search panels definitions for alternative Backoffice search
     *
     * @param array $params
     *
     * @return array
     */
    public function hookActionGetAlternativeSearchPanels(array $params): array
    {
        try {
            /** @var \Symfony\Bundle\FrameworkBundle\Routing\Router $router */
            $router = $this->get('router');
        } catch (\Exception $e) {
            return '';
        }

        $catalogUrl = $router->generate('admin_mbo_catalog_module', [], Router::ABSOLUTE_PATH);
        $catalogUrlPath = parse_url($catalogUrl, PHP_URL_PATH);
        parse_str(parse_url($catalogUrl, PHP_URL_QUERY), $catalogUrlParams);

        $searchedExpression = $params['bo_query'];
        if (!empty(trim($searchedExpression))) {
            $catalogUrlParams['keyword'] = trim($searchedExpression);
        }
        $catalogUrlParams['utm_mbo_source'] = 'search-back-office';
        $catalogUrlParams['mbo_cdc_path'] = '/#/modules';

        $searchPanels = [];
        $searchPanels[] = new SearchPanel(
            $this->trans('Find modules to grow your business', [], 'Modules.Mbo.Search'),
            $this->trans('Explore PrestaShop Marketplace', [], 'Modules.Mbo.Search'),
            $catalogUrlPath,
            $catalogUrlParams
        );

        return $searchPanels;
    }
}
