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

use ArrayIterator;

class TabCollection implements TabCollectionInterface
{
    /**
     * @var TabInterface[]
     */
    private $tabs = [];

    /**
     * {@inheritdoc}
     */
    public function addTab(TabInterface $tab)
    {
        $this->tabs[] = $tab;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTab($tabClassName)
    {
        foreach ($this->tabs as $tab) {
            if ($tabClassName === $tab->getLegacyClassName()) {
                return $tab;
            }
        }

        return new Tab();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->tabs);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->tabs[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->tabs[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->tabs[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->tabs);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->tabs);
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return empty($this->tabs);
    }
}
