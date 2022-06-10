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

namespace PrestaShop\Module\Mbo\Api\Config;

class Config
{
    const PROXY_TIMEOUT = 30;
    const REFRESH_TOKEN_ERROR_CODE = 452;
    const ENV_MISCONFIGURED_ERROR_CODE = 453;
    const DATABASE_QUERY_ERROR_CODE = 454;
    const DATABASE_INSERT_ERROR_CODE = 455;
    const INVALID_URL_QUERY = 458;

    const HTTP_STATUS_MESSAGES = [
        self::REFRESH_TOKEN_ERROR_CODE => 'Cannot refresh token',
        self::ENV_MISCONFIGURED_ERROR_CODE => 'Environment misconfigured',
        self::DATABASE_QUERY_ERROR_CODE => 'Database syntax error',
        self::DATABASE_INSERT_ERROR_CODE => 'Failed to write to database',
        self::INVALID_URL_QUERY => 'Invalid URL query',
    ];

    const COLLECTION_SHOPS = 'shops';
    const API_AUTHORIZATION = 'api_authorization';
}
