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

use PrestaShop\Module\Mbo\Module\Workflow\TransitionInterface;

class TransitionModule
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $installed;

    /**
     * @var bool
     */
    private $activeOnMobile;

    /**
     * @var bool
     */
    private $active;

    /**
     * @var string
     */
    private $version;

    public function __construct(
        string $name,
        string $version,
        bool $installed,
        bool $activeOnMobile,
        bool $active
    ) {
        $this->name = $name;
        $this->installed = $installed;
        $this->activeOnMobile = $activeOnMobile;
        $this->active = $active;
        $this->version = $version;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isInstalled(): bool
    {
        return $this->installed;
    }

    public function isActiveOnMobile(): bool
    {
        return $this->activeOnMobile;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getStatus(): string
    {
        if (!$this->installed) {
            return TransitionInterface::STATUS_UNINSTALLED;
        }

        if ($this->active && $this->activeOnMobile) {
            return TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED;
        }

        if ($this->active && !$this->activeOnMobile) {
            return TransitionInterface::STATUS_ENABLED__MOBILE_DISABLED;
        }

        if (!$this->active && $this->activeOnMobile) {
            return TransitionInterface::STATUS_DISABLED__MOBILE_ENABLED;
        }

        return TransitionInterface::STATUS_DISABLED__MOBILE_DISABLED;
    }
}
