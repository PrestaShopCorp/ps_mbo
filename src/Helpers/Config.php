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
    const AVAILABLE_SHOP_ACTIVITIES = [
        1 => 'Lingerie and Adult',
        2 => 'Animals and Pets',
        3 => 'Art and Culture',
        4 => 'Babies',
        5 => 'Beauty and Personal Care',
        6 => 'Cars',
        7 => 'Computer Hardware and Software',
        8 => 'Download',
        9 => 'Fashion and accessories',
        10 => 'Flowers, Gifts and Crafts',
        11 => 'Food and beverage',
        12 => 'HiFi, Photo and Video',
        13 => 'Home and Garden',
        14 => 'Home Appliances',
        15 => 'Jewelry',
        16 => 'Mobile and Telecom',
        17 => 'Services',
        18 => 'Shoes and accessories',
        19 => 'Sport and Entertainment',
        20 => 'Travel',
    ];

    /**
     * @var string|null
     */
    private static $SHOP_MBO_UUID;

    /**
     * @var string|null
     */
    private static $SHOP_MBO_ADMIN_MAIL;

    /**
     * @var string|null
     */
    private static $SHOP_URL_WITHOUT_PHYSICAL_URI;

    /**
     * @var string|null
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
            $singleShop = self::getDefaultShop();

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
            $singleShop = self::getDefaultShop();

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
    public static function getShopUrl(bool $withPhysicalUri = true): ?string
    {
        if (!$withPhysicalUri && null !== self::$SHOP_URL_WITHOUT_PHYSICAL_URI) {
            return self::$SHOP_URL_WITHOUT_PHYSICAL_URI;
        }

        if (null === self::$SHOP_URL) {
            $singleShop = self::getDefaultShop();
            $domains = \Tools::getDomains();

            $shopDomain = array_filter(
                $domains,
                function($domain) use($singleShop) {
                    // Here we assume that every shop have a single domain (?)
                    $domain = reset($domain);
                    return isset($domain['id_shop']) && (int)$singleShop->id === (int)$domain['id_shop'];
                }
            );

            $useSecureProtocol = self::isUsingSecureProtocol();
            if (empty($shopDomain)) { // If somehow we failed getting the shop_url from ps_shop_url, do it the old way, with configuration values
                $domainConfigKey = $useSecureProtocol ? 'PS_SHOP_DOMAIN_SSL' : 'PS_SHOP_DOMAIN';

                $domain = Configuration::get(
                    $domainConfigKey,
                    null,
                    $singleShop->id_shop_group,
                    $singleShop->id
                );

                if ($domain) {
                    $domain = preg_replace('#(https?://)#', '', $domain);
                    self::$SHOP_URL = self::$SHOP_URL_WITHOUT_PHYSICAL_URI = ($useSecureProtocol ? 'https://' : 'http://') . $domain;
                }
            } else {
                $domain = array_keys($shopDomain)[0];
                $domain = preg_replace('#(https?://)#', '', $domain);

                self::$SHOP_URL_WITHOUT_PHYSICAL_URI = ($useSecureProtocol ? 'https://' : 'http://') . $domain;

                // concatenate the physical_uri
                $domainDef = reset($shopDomain[$domain]);
                if (isset($domainDef['physical']) && '/' !== $domainDef['physical']) {
                    $domain .= $domainDef['physical'];
                }

                self::$SHOP_URL = ($useSecureProtocol ? 'https://' : 'http://') . $domain;
            }
        }

        return $withPhysicalUri ? self::$SHOP_URL : self::$SHOP_URL_WITHOUT_PHYSICAL_URI;
    }

    /**
     * @return bool
     */
    public static function isUsingSecureProtocol(): bool
    {
        $singleShop = self::getDefaultShop();

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
        $singleShop = self::getDefaultShop();

        return Configuration::get(
            'PS_MBO_LAST_PS_VERSION_API_CONFIG',
            null,
            $singleShop->id_shop_group,
            $singleShop->id,
            null
        );
    }

    public static function getDefaultShop(): Shop
    {
        return new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
    }

    /**
     * @return array{
     *     "id": int|null,
     *     "name": string|null
     * }
     */
    public static function getShopActivity(): array
    {
        $singleShop = self::getDefaultShop();
        $activity = [
            'id' => null,
            'name' => null,
        ];

        $activityId = (int) Configuration::get(
            'PS_SHOP_ACTIVITY',
            null,
            $singleShop->id_shop_group,
            $singleShop->id,
            null
        );

        if (empty($activityId)) {
            return $activity;
        }

        $activity['id'] = $activityId;
        $activity['name'] = self::AVAILABLE_SHOP_ACTIVITIES[$activityId] ?? 'Unknown';

        return $activity;
    }
}
