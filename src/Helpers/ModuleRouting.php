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

use PrestaShop\Module\Mbo\Module\Module;
use Tools;

class ModuleRouting
{
    public static function getConfigUrl(Module $module): ?string
    {
        $moduleUrls = $module->get('urls');
        $configUrl = (bool) $module->get('is_configurable') && isset($moduleUrls['configure']) ? self::generateTokenizedModuleActionUrl($moduleUrls['configure']) : null;

        return $configUrl;
    }


    private static function generateTokenizedModuleActionUrl($url): string
    {
        $components = parse_url($url);
        $baseUrl = ($components['path'] ?? '');
        $queryParams = [];
        if (isset($components['query'])) {
            $query = $components['query'];

            parse_str($query, $queryParams);
        }

        if (!isset($queryParams['_token'])) {
            return $url;
        }

        $adminToken = Tools::getValue('admin_token');
        $queryParams['_token'] = $adminToken;

        $url = $baseUrl . '?' . http_build_query($queryParams, '', '&');
        if (isset($components['fragment']) && $components['fragment'] !== '') {
            /* This copy-paste from Symfony's UrlGenerator */
            $url .= '#' . strtr(rawurlencode($components['fragment']), ['%2F' => '/', '%3F' => '?']);
        }

        return $url;
    }

}
