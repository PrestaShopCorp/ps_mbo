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

namespace PrestaShop\Module\Mbo\Tab;

use PrestaShop\Module\Mbo\RecommendedModule\RecommendedModuleCollection;
use PrestaShop\Module\Mbo\RecommendedModule\RecommendedModuleCollectionInterface;

class Tab implements TabInterface
{
    /**
     * @var string class name of the tab
     */
    protected $legacyClassName;

    /**
     * @var string class name of the tab
     */
    protected $displayMode;

    /**
     * @var RecommendedModuleCollectionInterface recommended modules of the tab
     */
    protected $recommendedModules;

    /**
     * Tab constructor.
     */
    public function __construct()
    {
        $this->recommendedModules = new RecommendedModuleCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getLegacyClassName(): string
    {
        return $this->legacyClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function setLegacyClassName(string $legacyClassName): TabInterface
    {
        $this->legacyClassName = $legacyClassName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayMode(): string
    {
        return $this->displayMode;
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayMode(string $displayMode): TabInterface
    {
        $this->displayMode = $displayMode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecommendedModules(): RecommendedModuleCollectionInterface
    {
        return $this->recommendedModules;
    }

    /**
     * {@inheritdoc}
     */
    public function setRecommendedModules(RecommendedModuleCollectionInterface $recommendedModules): TabInterface
    {
        $this->recommendedModules = $recommendedModules;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRecommendedModules(): bool
    {
        return !$this->recommendedModules->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function getRecommendedModulesInstalled(): RecommendedModuleCollectionInterface
    {
        $recommendedModulesInstalled = $this->getRecommendedModules();

        if ($this->hasRecommendedModules()) {
            return $recommendedModulesInstalled->getInstalled();
        }

        return $recommendedModulesInstalled;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecommendedModulesNotInstalled(): RecommendedModuleCollectionInterface
    {
        $recommendedModulesNotInstalled = $this->getRecommendedModules();

        if ($this->hasRecommendedModules()) {
            return $recommendedModulesNotInstalled->getNotInstalled();
        }

        return $recommendedModulesNotInstalled;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldDisplayButton(): bool
    {
        return $this->hasRecommendedModules()
            && TabInterface::DISPLAY_MODE_MODAL === $this->getDisplayMode();
    }

    /**
     * {@inheritdoc}
     */
    public function shouldDisplayAfterContent(): bool
    {
        return $this->hasRecommendedModules()
            && TabInterface::DISPLAY_MODE_AFTER_CONTENT === $this->getDisplayMode();
    }
}
