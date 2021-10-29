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

namespace PrestaShop\Module\Mbo\Module;

use PrestaShop\Module\Mbo\Module\Filter;


class Filter
{
    /**
     * @var array<string, int>
     */
    protected $flags = [
        Filter\Origin::FLAG => Filter\Origin::ALL,
        Filter\Status::FLAG => Filter\Status::ALL,
        Filter\Type::FLAG => Filter\Type::ALL,
    ];

    /*
     * Note: these functions are protected to prevent outside code
     * from falsely setting BITS. See how the extending class 'User'
     * handles this.
     */
    protected function isFlagSet(string $type, int $flag): bool
    {
        return (($this->flags[$type] & $flag) === $flag);
    }

    /**
     * @param int $origin
     *
     * @return self
     */
    public function setOrigin(int $origin): self
    {
        $this->flags[Filter\Origin::FLAG] = $origin;

        return $this;
    }

    /**
     * @param int $status
     *
     * @return self
     */
    public function setStatus(int $status): self
    {
        $this->flags[Filter\Status::FLAG] = $status;

        return $this;
    }

    /**
     * @param int $type
     *
     * @return self
     */
    public function setType(int $type): self
    {
        $this->flags[Filter\Type::FLAG] = $type;

        return $this;
    }

    /**
     * @param int $origin
     *
     * @return bool
     */
    public function hasOrigin(int $origin): bool
    {
        return $this->isFlagSet(Filter\Origin::FLAG, $origin);
    }

    /**
     * @param int $status
     *
     * @return bool
     */
    public function hasStatus(int $status): bool
    {
        return $this->isFlagSet(Filter\Status::FLAG, $status);
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    public function hasType(int $type): bool
    {
        return $this->isFlagSet(Filter\Type::FLAG, $type);
    }
}
