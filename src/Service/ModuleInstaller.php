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

namespace PrestaShop\Module\Mbo\Service;

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;

class ModuleInstaller
{
    private $moduleName;
    private $moduleVersion;
    private $absoluteCompare;
    private $moduleManager;

    /**
     * @param string $moduleName
     * @param string|null $moduleVersion
     * @param bool $absoluteCompare
     */
    public function __construct(string $moduleName, string $moduleVersion = null, bool $absoluteCompare = false)
    {
        $this->moduleName = $moduleName;
        $this->moduleVersion = $moduleVersion;
        $this->absoluteCompare = $absoluteCompare;
        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        $this->moduleManager = $moduleManagerBuilder->build();
    }

    /**
     * Install ps_accounts module if not installed
     * Method to call in every psx modules during the installation process
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function install()
    {
        if ($this->isModuleInstalled() && $this->isModuleVersionSatisfied()) {
            return true;
        }

        return $this->moduleManager->install($this->moduleName);
    }

    /**
     * @return bool
     */
    public function enable()
    {
        if (!$this->moduleManager->isEnabled($this->moduleName)) {
            return $this->moduleManager->enable($this->moduleName);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isShopVersion17()
    {
        /* @SuppressWarnings("php:S1313") */
        return version_compare(_PS_VERSION_, '1.7.0.0', '>=');
    }

    /**
     * @return bool
     */
    public function isModuleInstalled()
    {
        if (false === $this->isShopVersion17()) {
            return \Module::isInstalled($this->moduleName);
        }

        return $this->moduleManager->isInstalled($this->moduleName);
    }

    /**
     * @return bool
     */
    public function isModuleVersionSatisfied()
    {
        if (!$this->moduleVersion) {
            return true;
        }
        $module = \Module::getInstanceByName($this->moduleName);

        return version_compare(
            $module->version,
            $this->moduleVersion,
            $this->absoluteCompare ? '>' : '>='
        );
    }
}
