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

class RecommendedModule implements RecommendedModuleInterface
{
    /**
     * @var string technical name of the recommended module
     */
    private $moduleName;

    /**
     * @var int position of the recommended module
     */
    private $position;

    /**
     * @var bool
     */
    private $isInstalled;

    /**
     * @var array
     */
    private $moduleData;

    /**
     * {@inheritdoc}
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * {@inheritdoc}
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled()
    {
        return $this->isInstalled;
    }

    /**
     * {@inheritdoc}
     */
    public function setInstalled($isInstalled)
    {
        $this->isInstalled = $isInstalled;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getModuleData()
    {
        return $this->moduleData;
    }

    /**
     * {@inheritdoc}
     */
    public function setModuleData($moduleData)
    {
        $this->moduleData = $moduleData;

        return $this;
    }
}
