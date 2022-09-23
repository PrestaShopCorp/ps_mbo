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

namespace PrestaShop\Module\Mbo\Module;

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
    protected $data;

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
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key): mixed
    {
        return $this->offsetExists($key) ? $this->data[$key] : null;
    }

    /**
     * Required by ArrayAccess interface.
     *
     * {@inheritdoc}
     */
    public function offsetSet($key, $module): void
    {
        $this->data[$key] = $module;
    }

    /**
     * Required by interface ArrayAccess.
     *
     * {@inheritdoc}
     */
    public function offsetUnset($module): void
    {
        $key = array_search($module, $this->data, true);

        if ($key === false) {
            return;
        }

        unset($this->data[$key]);
    }

    /**
     * Gets the sum of addons of the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }
}
