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

namespace PrestaShop\Module\Mbo\Modules;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * An ArrayCollection is a Collection implementation that wraps a regular PHP array.
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * An array containing the addons of this collection.
     *
     * @var array
     */
    private $data;

    /**
     * Initializes a new AddonsCollection.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$key]) || array_key_exists($key, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        return $this->data[$key] ? $this->data[$key] : null;
    }

    /**
     * Required by ArrayAccess interface.
     *
     * {@inheritdoc}
     */
    public function offsetSet($key, $module)
    {
        $this->data[$key] = $module;
    }

    /**
     * Required by interface ArrayAccess.
     *
     * {@inheritdoc}
     */
    public function offsetUnset($module)
    {
        $key = array_search($module, $this->data, true);

        if ($key === false) {
            return false;
        }

        unset($this->data[$key]);

        return true;
    }

    /**
     * Gets the sum of addons of the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }
}
