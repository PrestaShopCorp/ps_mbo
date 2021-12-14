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

namespace PrestaShop\Module\Mbo\RecommendedModule;

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleInterface;

interface RecommendedModuleInterface
{
    /**
     * Get the technical name of the recommended module.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     *
     * @return RecommendedModuleInterface
     */
    public function setName(string $name): self;

    /**
     * Get the position of the recommended module.
     *
     * @return int
     */
    public function getPosition(): int;

    /**
     * @param int $position
     *
     * @return RecommendedModuleInterface
     */
    public function setPosition(int $position): self;

    /**
     * Check if the recommended modules is installed.
     *
     * @return bool
     */
    public function isInstalled(): bool;

    /**
     * @param bool $isInstalled
     *
     * @return RecommendedModuleInterface
     */
    public function setInstalled(bool $isInstalled): self;

    /**
     * Get the recommended module data.
     *
     * @return ModuleInterface
     */
    public function getModule(): ModuleInterface;

    /**
     * @param ModuleInterface $module
     *
     * @return RecommendedModuleInterface
     */
    public function setModule(ModuleInterface $module): self;
}
