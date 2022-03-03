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

namespace PrestaShop\Module\Mbo\News;

use Context;
use Tools;

class NewsBuilder
{
    /**
     * @var string[]
     */
    private static $analyticsParams;

    public static function build(
        string $date,
        string $title,
        string $description,
        string $link,
        string $countryIsoCode,
        int $contextMode
    ): News {
        $analyticParams = self::getAnalyticsParams($countryIsoCode, $contextMode);

        return new News(
            self::formatDate($date),
            self::formatTitle($title),
            self::formatDescription($description),
            self::buildLink($link, $analyticParams)
        );
    }

    private static function formatDate(string $date): string
    {
        $date = strtotime($date);

        return Tools::displayDate(date('Y-m-d H:i:s', $date), null, false);
    }

    private static function formatTitle(string $title): string
    {
        return htmlentities($title, ENT_QUOTES, 'utf-8');
    }

    private static function formatDescription(string $description)
    {
        return Tools::truncateString(strip_tags($description), 150);
    }

    private static function buildLink(string $link, array $analyticParams): string
    {
        $url_query = parse_url($link, PHP_URL_QUERY) ?? '';
        parse_str($url_query, $link_query_params);
        $full_url_params = array_merge($link_query_params, $analyticParams);
        $base_url = explode('?', $link);
        $base_url = (string) $base_url[0];

        return $base_url . '?' . http_build_query($full_url_params);
    }

    private static function getAnalyticsParams(string $countryIsoCode, int $contextMode): array
    {
        if (null !== self::$analyticsParams) {
            return self::$analyticsParams;
        }

        self::$analyticsParams = [
            'utm_source' => 'back-office',
            'utm_medium' => 'rss',
            'utm_campaign' => 'back-office-' . $countryIsoCode,
        ];

        self::$analyticsParams['utm_content'] = 'download';
        if (in_array($contextMode, [Context::MODE_HOST, Context::MODE_HOST_CONTRIB])) {
            self::$analyticsParams['utm_content'] = 'cloud';
        }

        return self::$analyticsParams;
    }
}
