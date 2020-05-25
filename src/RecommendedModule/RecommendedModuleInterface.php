<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Mbo\RecommendedModule;

interface RecommendedModuleInterface
{
    /**
     * Get the technical name of the recommended module.
     *
     * @return string
     */
    public function getModuleName();

    /**
     * @param string $moduleName
     *
     * @return RecommendedModuleInterface
     */
    public function setModuleName($moduleName);

    /**
     * Get the position of the recommended module.
     *
     * @return int
     */
    public function getPosition();

    /**
     * @param int $position
     *
     * @return RecommendedModuleInterface
     */
    public function setPosition($position);

    /**
     * Check if the recommended modules is installed.
     *
     * @return bool
     */
    public function isInstalled();

    /**
     * @param bool $isInstalled
     *
     * @return RecommendedModuleInterface
     */
    public function setInstalled($isInstalled);

    /**
     * Get the recommended module data.
     *
     * @return array
     */
    public function getModuleData();

    /**
     * @param array $moduleData
     *
     * @return RecommendedModuleInterface
     */
    public function setModuleData($moduleData);
}
