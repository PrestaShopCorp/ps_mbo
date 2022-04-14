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

namespace PrestaShop\Module\Mbo\Addons;

use PrestaShop\Module\Mbo\Addons\Provider\LinksProvider;

class PracticalLinks
{
    private const COMMON_PATTERN = 'https://addons.prestashop.com/%s/2-modules-prestashop?m=1&benefits=%d&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-%s&utm_content=%s';
    private const BUSINESS_SECTOR_PATTERN = 'https://addons.prestashop.com/%s/content/44-ressources-prestashop-les-outils-pour-reussir?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-%s&utm_content=%s';
    private const BENEFITS_TRAFFIC = 6;
    private const UTM_CONTENT_TRAFFIC = 'traffic';
    private const BENEFITS_CONVERSION = 1;
    private const UTM_CONTENT_CONVERSION = 'conversions';
    private const BENEFITS_CART = 3;
    private const UTM_CONTENT_CART = 'cart';
    private const UTM_CONTENT_BUSINESS_SECTOR = 'sector#modulebusinesssector';

    public static function getByIsoCode(string $isoCode): array
    {
        if (!in_array($isoCode, LinksProvider::ADDONS_LANGUAGES)) {
            $isoCode = LinksProvider::DEFAULT_LANGUAGE;
        }

        return [
            'traffic' => sprintf(self::COMMON_PATTERN, mb_strtolower($isoCode), self::BENEFITS_TRAFFIC, mb_strtoupper($isoCode), self::UTM_CONTENT_TRAFFIC),
            'conversion' => sprintf(self::COMMON_PATTERN, mb_strtolower($isoCode), self::BENEFITS_CONVERSION, mb_strtoupper($isoCode), self::UTM_CONTENT_CONVERSION),
            'averageCart' => sprintf(self::COMMON_PATTERN, mb_strtolower($isoCode), self::BENEFITS_CART, mb_strtoupper($isoCode), self::UTM_CONTENT_CART),
            'businessSector' => sprintf(self::BUSINESS_SECTOR_PATTERN, mb_strtolower($isoCode), mb_strtoupper($isoCode), self::UTM_CONTENT_BUSINESS_SECTOR),
        ];
    }
}
