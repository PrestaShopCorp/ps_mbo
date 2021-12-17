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

class RecommendedModule implements RecommendedModuleInterface
{
    /**
     * @var string technical name of the recommended module
     */
    protected $name;

    /**
     * @var int position of the recommended module
     */
    protected $position;

    /**
     * @var bool
     */
    protected $isInstalled;

    /**
     * @var ModuleInterface
     */
    protected $module;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): RecommendedModuleInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function setPosition(int $position): RecommendedModuleInterface
    {
        $this->position = $position;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled(): bool
    {
        return $this->isInstalled;
    }

    /**
     * {@inheritdoc}
     */
    public function setInstalled(bool $isInstalled): RecommendedModuleInterface
    {
        $this->isInstalled = $isInstalled;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getModule(): ModuleInterface
    {
        return $this->module;
    }

    /**
     * {@inheritdoc}
     */
    public function setModule(ModuleInterface $module): RecommendedModuleInterface
    {
        $this->module = $module;

        return $this;
    }
}
