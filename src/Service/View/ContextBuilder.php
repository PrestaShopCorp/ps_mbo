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

namespace PrestaShop\Module\Mbo\Service\View;

use Configuration;
use Context;
use Language;
use PrestaShop\PrestaShop\Adapter\LegacyContext as ContextAdapter;
use Tools;

class ContextBuilder
{
    public const DEFAULT_CURRENCY_CODE = 'EUR';

    /**
     * @var ContextAdapter
     */
    private $contextAdapter;

    public function __construct(ContextAdapter $contextAdapter)
    {
        $this->contextAdapter = $contextAdapter;
    }

    public function getViewContext(): array
    {
        $context = $this->getContext();
        $language = $this->getLanguage();

        return [
            'currency' => $this->getCurrencyCode(),
            'isoLang' => $language->getLanguageCode(),
            'isoCode' => $language->getIsoCode(),
            'shopVersion' => _PS_VERSION_,
            'shopUrl' => $context->shop->getBaseURL(true, false),
            // The token is constant string for now, it'll be replaced by the user's real token when security layer will be implemented
            'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwidXNlcm5hbWUiOiJzdWxsaXZhbi5tb250ZWlyb0BwcmVzdGFzaG9wLmNvbSIsImlhdCI6MTUxNjIzOTAyMn0.2u4JjKhORcCbIfY6WqJ1Fks1nVfQiEaXSd4GGxMDghU',
            'prestaShopControllerClassName' => Tools::getValue('controller'),
        ];
    }

    private function getContext(): Context
    {
        return $this->contextAdapter->getContext();
    }

    private function getLanguage(): Language
    {
        return $this->getContext()->language ?? new Language((int) Configuration::get('PS_LANG_DEFAULT'));
    }

    private function getCurrencyCode(): string
    {
        $currency = $this->getContext()->currency;

        if (null === $currency || !in_array($currency->iso_code, ['EUR', 'USD', 'GBP'])) {
            return self::DEFAULT_CURRENCY_CODE;
        }

        return $currency->iso_code;
    }
}
