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

namespace PrestaShop\Module\Mbo\Module\Workflow;

interface TransitionInterface
{
    public const NO_CHANGE_TRANSITION = 'no_change_transition';

    public const STATUS_UNINSTALLED = 'uninstalled';
    public const STATUS_ENABLED__MOBILE_ENABLED = 'enabled__mobile_enabled';
    public const STATUS_ENABLED__MOBILE_DISABLED = 'enabled__mobile_disabled';
    public const STATUS_DISABLED__MOBILE_ENABLED = 'disabled__mobile_enabled';
    public const STATUS_DISABLED__MOBILE_DISABLED = 'disabled__mobile_disabled';
    public const STATUS_RESET = 'reset'; //virtual status
    public const STATUS_UPGRADED = 'upgraded'; //virtual status
    public const STATUS_CONFIGURED = 'configured'; //virtual status

    public const STATUSES = [
        self::STATUS_UNINSTALLED,
        self::STATUS_ENABLED__MOBILE_ENABLED,
        self::STATUS_ENABLED__MOBILE_DISABLED,
        self::STATUS_DISABLED__MOBILE_ENABLED,
        self::STATUS_DISABLED__MOBILE_DISABLED,
        self::STATUS_RESET,
        self::STATUS_UPGRADED,
        self::STATUS_CONFIGURED,
    ];

    public function getFromStatus(): string;

    public function getToStatus(): string;

    public function getTransitionName(): string;
}
