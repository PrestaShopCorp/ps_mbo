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
        $searchedExpression = $params['bo_query'];

        $version = defined('_PS_VERSION_') ? _PS_VERSION_ : '';
        if ($lastDotIndex = strrpos($version, '.')) {
            $trailingVersion = str_replace('.', '_', substr($version, 0, $lastDotIndex));
        } else {
            $trailingVersion = '';
        }

        $queryParams = [
            'search_query' => $searchedExpression,
            'utm_source' => 'back-office',
            'utm_medium' => 'search',
            'utm_campaign' => 'back-office-' . $this->context->language->iso_code,
            'utm_content' => 'download' . $trailingVersion,
        ];

        $searchPanels = [];
        $searchPanels[] = new SearchPanel(
            $this->trans('Search addons.prestashop.com', [], 'Modules.Mbo.Search'),
            $this->trans('Go to Addons', [], 'Modules.Mbo.Search'),
            'https://addons.prestashop.com/search.php',
            $queryParams
        );

        return $searchPanels;
    }
}
