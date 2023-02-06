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

/**
 * Helper class to define filters for modules list.
 */
class Filters implements FiltersInterface
{
    /**
     * @var array<string, int>
     */
    protected $flags = [
        Filters\Status::FLAG => Filters\Status::ALL,
        Filters\Type::FLAG => Filters\Type::ALL,
    ];

    /*
     * Note: these functions are protected to prevent outside code
     * from falsely setting BITS. See how the extending class 'User'
     * handles this.
     */
    protected function isFlagSet(string $type, int $flag): bool
    {
        return ($this->flags[$type] & $flag) === $flag;
    }

    /**
     * @param int $status
     *
     * @return FiltersInterface
     */
    public function setStatus(int $status): FiltersInterface
    {
        $this->flags[Filters\Status::FLAG] = $status;

        return $this;
    }

    /**
     * @param int $type
     *
     * @return FiltersInterface
     */
    public function setType(int $type): FiltersInterface
    {
        $this->flags[Filters\Type::FLAG] = $type;

        return $this;
    }

    /**
     * @param int $status
     *
     * @return bool
     */
    public function hasStatus(int $status): bool
    {
        return $this->isFlagSet(Filters\Status::FLAG, $status);
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    public function hasType(int $type): bool
    {
        return $this->isFlagSet(Filters\Type::FLAG, $type);
    }
}
