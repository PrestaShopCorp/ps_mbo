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

if (!defined('_PS_VERSION_')) {
    exit;
}

enum ModuleStatus: string
{
    case UNINSTALLED = 'uninstalled';
    case ENABLED_MOBILE_ENABLED = 'enabled__mobile_enabled';
    case ENABLED_MOBILE_DISABLED = 'enabled__mobile_disabled';
    case DISABLED_MOBILE_ENABLED = 'disabled__mobile_enabled';
    case DISABLED_MOBILE_DISABLED = 'disabled__mobile_disabled';
    case RESET = 'reset';
    case UPGRADED = 'upgraded';
    case CONFIGURED = 'configured';

    public static function fromFlags(bool $installed, bool $active, bool $mobileActive): self
    {
        if (!$installed) {
            return self::UNINSTALLED;
        }

        if ($active && $mobileActive) {
            return self::ENABLED_MOBILE_ENABLED;
        }

        if ($active) {
            return self::ENABLED_MOBILE_DISABLED;
        }

        if ($mobileActive) {
            return self::DISABLED_MOBILE_ENABLED;
        }

        return self::DISABLED_MOBILE_DISABLED;
    }
}
