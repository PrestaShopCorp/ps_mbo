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

namespace PrestaShop\Module\Mbo\Helpers;

use Configuration;
use Shop;

class Config
{
    /**
     * @var string
     */
    private static $SHOP_MBO_UUID;
    /**
     * @var string
     */
    private static $SHOP_MBO_ADMIN_MAIL;
    /**
     * @var string
     */
    private static $SHOP_URL;

    public static function resetConfigValues(): void
    {
        self::$SHOP_MBO_UUID = null;
        self::$SHOP_MBO_ADMIN_MAIL = null;
        self::$SHOP_URL = null;
    }

    public static function getShopMboUuid(): ?string
    {
        if (null === self::$SHOP_MBO_UUID) {
            // PS_MBO_SHOP_ADMIN_UUID have the same value for all shops
            // to prevent errors in a multishop context,
            // we request the shops list and get the config value for the 1st one
            $singleShop = self::getSingleShop();

            self::$SHOP_MBO_UUID = Configuration::get(
                'PS_MBO_SHOP_ADMIN_UUID',
                null,
                $singleShop->id_shop_group,
                $singleShop->id,
                null
            );
        }

        return self::$SHOP_MBO_UUID;
    }

    public static function getShopMboAdminMail(): ?string
    {
        if (null === self::$SHOP_MBO_ADMIN_MAIL) {
            // PS_MBO_SHOP_ADMIN_ADMIN_MAIL have the same value for all shops
            // to prevent errors in a multishop context,
            // we request the shops list and get the config value for the 1st one
            $singleShop = self::getSingleShop();

            self::$SHOP_MBO_ADMIN_MAIL = Configuration::get(
                'PS_MBO_SHOP_ADMIN_MAIL',
                null,
                $singleShop->id_shop_group,
                $singleShop->id,
                null
            );
        }

        return self::$SHOP_MBO_ADMIN_MAIL;
    }

    /**
     * The Tools::usingSecureMode used by Shop::getBaseUrl seems to not work in all situations
     * To be sure to have the correct data, use shop configuration
     *
     * @return string|null
     */
    public static function getShopUrl(): ?string
    {
        if (null === self::$SHOP_URL) {
            $singleShop = self::getSingleShop();
            $useSecureProtocol = self::isUsingSecureProtocol();
            $domainConfigKey = $useSecureProtocol ? 'PS_SHOP_DOMAIN_SSL' : 'PS_SHOP_DOMAIN';

            $domain = Configuration::get(
                $domainConfigKey,
                null,
                $singleShop->id_shop_group,
                $singleShop->id
            );

            if ($domain) {
                $domain = preg_replace('#(https?://)#', '', $domain);
                self::$SHOP_URL = ($useSecureProtocol ? 'https://' : 'http://') . $domain;
            }
        }

        return self::$SHOP_URL;
    }

    /**
     * @return bool
     */
    public static function isUsingSecureProtocol(): bool
    {
        $singleShop = self::getSingleShop();

        return (bool) Configuration::get(
            'PS_SSL_ENABLED',
            null,
            $singleShop->id_shop_group,
            $singleShop->id
        );
    }

    public static function getLastPsVersionApiConfig(): ?string
    {
        // PS_MBO_LAST_PS_VERSION_API_CONFIG have the same value for all shops
        // to prevent errors in a multishop context,
        // we request the shops list and get the config value for the 1st one
        $singleShop = self::getSingleShop();

        return Configuration::get(
            'PS_MBO_LAST_PS_VERSION_API_CONFIG',
            null,
            $singleShop->id_shop_group,
            $singleShop->id,
            null
        );
    }

    private static function getSingleShop(): Shop
    {
        $shops = Shop::getShops(false, null, true);

        return new Shop((int) reset($shops));
    }
}
