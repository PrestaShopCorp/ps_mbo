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

namespace PrestaShop\Module\Mbo\Api\Factory;

use Context;
use Cookie;
use Currency;
use Link;
use Shop;
use Smarty;

class ContextFactory
{
    public static function getContext(): ?Context
    {
        return Context::getContext();
    }

    public static function getLanguage()
    {
        return Context::getContext()->language;
    }

    public static function getCurrency(): ?Currency
    {
        return Context::getContext()->currency;
    }

    public static function getSmarty(): ?Smarty
    {
        return Context::getContext()->smarty;
    }

    public static function getShop(): ?Shop
    {
        return Context::getContext()->shop;
    }

    public static function getController()
    {
        return Context::getContext()->controller;
    }

    public static function getCookie(): ?Cookie
    {
        return Context::getContext()->cookie;
    }

    public static function getLink(): ?Link
    {
        return Context::getContext()->link;
    }
}
