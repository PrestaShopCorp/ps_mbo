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
    const UNAUTHORIZED_ERROR_CODE = 401;
    const DATABASE_QUERY_ERROR_CODE = 454;
    const DATABASE_INSERT_ERROR_CODE = 455;
    const RETRIEVE_NEW_KEY_ERROR_CODE = 457;
    const INVALID_URL_QUERY = 458;
    const INCOMPLETE_SIGNATURE_ERROR_CODE = 459;

    const HTTP_STATUS_MESSAGES = [
        self::DATABASE_QUERY_ERROR_CODE => 'Database syntax error',
        self::DATABASE_INSERT_ERROR_CODE => 'Failed to write to database',
        self::INVALID_URL_QUERY => 'Invalid URL query',
        self::UNAUTHORIZED_ERROR_CODE => 'Not authorized',
        self::INCOMPLETE_SIGNATURE_ERROR_CODE => 'Incomplete signature',
        self::RETRIEVE_NEW_KEY_ERROR_CODE => 'Failed to retrieve key',
    ];

    const API_CONFIG = 'api_config';
    const MODULE_ACTIONS = 'module_actions';
    const SECURITY_ME = 'security_me';
}
