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

namespace PrestaShop\Module\Mbo\Tab;

use PrestaShop\Module\Mbo\RecommendedModule\RecommendedModuleCollectionInterface;

interface TabInterface
{
    const DISPLAY_MODE_MODAL = 'slider_list';

    const DISPLAY_MODE_AFTER_CONTENT = 'default_list';

    /**
     * Get the class name of the tab.
     *
     * @return string
     */
    public function getLegacyClassName();

    /**
     * @param string $legacyClassName
     *
     * @return TabInterface
     */
    public function setLegacyClassName($legacyClassName);

    /**
     * Get the display mode of the tab.
     *
     * @return string
     */
    public function getDisplayMode();

    /**
     * @param string $displayMode
     *
     * @return TabInterface
     */
    public function setDisplayMode($displayMode);

    /**
     * Get the recommended modules of the tab.
     *
     * @return RecommendedModuleCollectionInterface
     */
    public function getRecommendedModules();

    /**
     * @param RecommendedModuleCollectionInterface $recommendedModules
     *
     * @return TabInterface
     */
    public function setRecommendedModules(RecommendedModuleCollectionInterface $recommendedModules);

    /**
     * Check if the tab has recommended modules.
     *
     * @return bool
     */
    public function hasRecommendedModules();

    /**
     * Get the installed recommended modules of the tab.
     *
     * @return RecommendedModuleCollectionInterface
     */
    public function getRecommendedModulesInstalled();

    /**
     * Get the not installed recommended modules of the tab.
     *
     * @return RecommendedModuleCollectionInterface
     */
    public function getRecommendedModulesNotInstalled();

    /**
     * @return bool
     */
    public function shouldDisplayButton();

    /**
     * @return bool
     */
    public function shouldDisplayAfterContent();
}
