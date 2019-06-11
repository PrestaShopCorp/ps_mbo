<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Mbo\TabsRecommendedModules;

use PrestaShop\Module\Mbo\RecommendedModules\RecommendedModulesInterface;

interface TabRecommendedModulesInterface
{
    const DISPLAY_MODE_MODAL = 'slider_list';

    const DISPLAY_MODE_AFTER_CONTENT = 'default_list';

    /**
     * Get the class name of the tab.
     *
     * @return string
     */
    public function getClassName();

    /**
     * Get the display mode of the tab.
     *
     * @return string
     */
    public function getDisplayMode();

    /**
     * Get the recommended modules of the tab.
     *
     * @return RecommendedModulesInterface
     */
    public function getRecommendedModules();

    /**
     * Check if the tab has recommended modules.
     *
     * @return bool
     */
    public function hasRecommendedModules();
}
