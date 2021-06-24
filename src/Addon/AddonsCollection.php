<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace PrestaShop\Module\Mbo\Addon;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use PrestaShop\Module\Mbo\Addon\Module as AddonModule;

/**
 * An ArrayCollection is a Collection implementation that wraps a regular PHP array.
 */
class AddonsCollection implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * An array containing the addons of this collection.
     *
     * @var array
     */
    private $collection;

    /**
     * Initializes a new AddonsCollection.
     *
     * @param array $collection
     */
    public function __construct(array $collection = [])
    {
        $this->collection = $collection;
    }

    /**
     * Creates a new instance from the specified elements.
     *
     * This method is provided for derived classes to specify how a new
     * instance should be created when constructor semantics have changed.
     *
     * @param array $collection elements
     *
     * @return static
     */
    public static function createFrom(array $collection)
    {
        return new static($collection);
    }

    /**
     * Gets a native PHP array representation of the collection.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->collection;
    }

    /**
     * @return ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->collection);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->containsKey($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Required by ArrayAccess interface.
     *
     * {@inheritdoc}
     */
    public function offsetSet($offset, $addon)
    {
        if (!isset($offset)) {
            $this->add($addon);

            return;
        }

        $this->set($offset, $addon);
    }

    /**
     * Required by interface ArrayAccess.
     *
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Returns true if the key is found in the collection.
     *
     * @param mixed $key the key, can be integer or string
     *
     * @return bool
     */
    public function containsKey($key)
    {
        return isset($this->collection[$key]) || array_key_exists($key, $this->collection);
    }

    /**
     * Returns true if the addon is found in the collection.
     *
     * @param AddonModule $module the addon
     *
     * @return bool
     */
    public function contains(AddonModule $module)
    {
        return in_array($module, $this->collection, true);
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf(AddonModule $module)
    {
        return array_search($module, $this->collection, true);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->collection[$key] ? $this->collection[$key] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys()
    {
        return array_keys($this->collection);
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        return array_values($this->collection);
    }

    /**
     * Add an AddonModule with a specified key in the collection.
     *
     * @param mixed $key the key
     * @param AddonModule $addon the specified addon
     */
    public function set($key, AddonModule $module)
    {
        $this->collection[$key] = $module;
    }

    /**
     * Add an AddonModule in the collection.
     *
     * @param AddonModule $module the specified addon
     *
     * @return bool
     */
    public function add(AddonModule $module)
    {
        $this->collection[] = $addon;

        return true;
    }

    /**
     * Remove an addon from the collection by key.
     *
     * @param int|string $key
     *
     * @return bool|null true if the addon has been found and removed
     */
    public function removeByKey($key)
    {
        if (!array_key_exists($key, $this->collection)) {
            return null;
        }

        $removed = $this->collection[$key];
        unset($this->collection[$key]);

        return $removed;
    }

    /**
     * Remove an addon from the collection by key.
     *
     * @param AddonModule $module the addon to be removed
     *
     * @return bool true if the addon has been found and removed
     */
    public function remove(AddonModule $module)
    {
        $key = array_search($module, $this->collection, true);

        if ($key === false) {
            return false;
        }

        unset($this->collection[$key]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return empty($this->collection);
    }

    /**
     * Gets the sum of addons of the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->collection);
    }
}
