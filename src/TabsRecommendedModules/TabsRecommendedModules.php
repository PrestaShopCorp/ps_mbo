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

use ArrayIterator;

class TabsRecommendedModules implements TabsRecommendedModulesInterface
{
    /**
     * @var TabRecommendedModulesInterface[]
     */
    private $tabsRecommendedModules = [];

    /**
     * {@inheritdoc}
     */
    public function addTab(TabRecommendedModulesInterface $tab)
    {
        $this->tabsRecommendedModules[] = $tab;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTab($tabClassName)
    {
        foreach ($this->tabsRecommendedModules as $tabRecommendedModules) {
            if ($tabClassName === $tabRecommendedModules->getClassName()) {
                return $tabRecommendedModules;
            }
        }

        return new TabRecommendedModules($tabClassName);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->tabsRecommendedModules);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->tabsRecommendedModules[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->tabsRecommendedModules[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->tabsRecommendedModules[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->tabsRecommendedModules);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->tabsRecommendedModules);
    }
}
