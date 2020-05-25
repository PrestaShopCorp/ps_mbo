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

namespace PrestaShop\Module\Mbo\Tab;

use PrestaShop\Module\Mbo\RecommendedModule\RecommendedModuleCollection;
use PrestaShop\Module\Mbo\RecommendedModule\RecommendedModuleCollectionInterface;

class Tab implements TabInterface
{
    /**
     * @var string class name of the tab
     */
    private $legacyClassName;

    /**
     * @var string class name of the tab
     */
    private $displayMode;

    /**
     * @var RecommendedModuleCollectionInterface recommended modules of the tab
     */
    private $recommendedModules;

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
    public function getLegacyClassName()
    {
        return $this->legacyClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function setLegacyClassName($legacyClassName)
    {
        $this->legacyClassName = $legacyClassName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayMode()
    {
        return $this->displayMode;
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayMode($displayMode)
    {
        $this->displayMode = $displayMode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecommendedModules()
    {
        return $this->recommendedModules;
    }

    /**
     * {@inheritdoc}
     */
    public function setRecommendedModules(RecommendedModuleCollectionInterface $recommendedModules)
    {
        $this->recommendedModules = $recommendedModules;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRecommendedModules()
    {
        return !$this->recommendedModules->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function getRecommendedModulesInstalled()
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
    public function getRecommendedModulesNotInstalled()
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
    public function shouldDisplayButton()
    {
        return $this->hasRecommendedModules()
            && TabInterface::DISPLAY_MODE_MODAL === $this->getDisplayMode();
    }

    /**
     * {@inheritdoc}
     */
    public function shouldDisplayAfterContent()
    {
        return $this->hasRecommendedModules()
            && TabInterface::DISPLAY_MODE_AFTER_CONTENT === $this->getDisplayMode();
    }
}
