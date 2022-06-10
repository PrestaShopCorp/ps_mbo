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

namespace PrestaShop\Module\Mbo\Api\Repository;

use Context;
use Language;
use PrestaShop\Module\Mbo\Api\Config\Config;
use ps_mbo;

class ServerInformationRepository
{
    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;

    /**
     * @var LanguageRepository
     */
    private $languageRepository;

    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var ShopRepository
     */
    private $shopRepository;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @var string
     */
    private $createdAt;

    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    public function __construct(
        Context $context,
        CurrencyRepository $currencyRepository,
        LanguageRepository $languageRepository,
        ConfigurationRepository $configurationRepository,
        ShopRepository $shopRepository,
        ModuleRepository $moduleRepository,
        array $configuration
    ) {
        $this->currencyRepository = $currencyRepository;
        $this->languageRepository = $languageRepository;
        $this->configurationRepository = $configurationRepository;
        $this->shopRepository = $shopRepository;
        $this->context = $context;
        $this->configuration = $configuration;
        $this->createdAt = $this->shopRepository->getCreatedAt();
        $this->moduleRepository = $moduleRepository;
    }

    public function getServerInformation(?string $langIso = null): array
    {
        $langId = $langIso != null ? (int) Language::getIdByIso($langIso) : null;

        return [
            [
                'id' => '1',
                'collection' => Config::COLLECTION_SHOPS,
                'properties' => [
                    'created_at' => $this->createdAt,
                    'cms_version' => _PS_VERSION_,
                    'url_is_simplified' => $this->configurationRepository->get('PS_REWRITING_SETTINGS') == '1',
                    'cart_is_persistent' => $this->configurationRepository->get('PS_CART_FOLLOWING') == '1',
                    'default_language' => $this->languageRepository->getDefaultLanguageIsoCode(),
                    'languages' => implode(';', $this->languageRepository->getLanguagesIsoCodes()),
                    'default_currency' => $this->currencyRepository->getDefaultCurrencyIsoCode(),
                    'currencies' => implode(';', $this->currencyRepository->getCurrenciesIsoCodes()),
                    'weight_unit' => $this->configurationRepository->get('PS_WEIGHT_UNIT'),
                    'distance_unit' => $this->configurationRepository->get('PS_BASE_DISTANCE_UNIT'),
                    'volume_unit' => $this->configurationRepository->get('PS_VOLUME_UNIT'),
                    'dimension_unit' => $this->configurationRepository->get('PS_DIMENSION_UNIT'),
                    'timezone' => $this->configurationRepository->get('PS_TIMEZONE'),
                    'is_order_return_enabled' => $this->configurationRepository->get('PS_ORDER_RETURN') == '1',
                    'order_return_nb_days' => (int) $this->configurationRepository->get('PS_ORDER_RETURN_NB_DAYS'),
                    'php_version' => phpversion(),
                    'http_server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '',
                    'url' => $this->context->link->getPageLink('index', null, $langId),
                    'ssl' => $this->configurationRepository->get('PS_SSL_ENABLED') == '1',
                    'multi_shop_count' => $this->shopRepository->getMultiShopCount(),
                ],
            ],
        ];
    }

    public function getHealthCheckData(): array
    {
        $tokenValid = false;
        $tokenIsSet = true;
        $allTablesInstalled = true;

        if (defined('PHP_VERSION') && defined('PHP_EXTRA_VERSION')) {
            $phpVersion = str_replace(PHP_EXTRA_VERSION, '', PHP_VERSION);
        } else {
            $phpVersion = (string) explode('-', (string) phpversion())[0];
        }

        return [
            'prestashop_version' => _PS_VERSION_,
            'ps_mbo_version' => ps_mbo::VERSION,
            'php_version' => $phpVersion,
            'ps_account' => $tokenIsSet,
            'is_valid_jwt' => $tokenValid,
            'ps_mbo' => $allTablesInstalled,
            'env' => [
                'PS_MBO_PROXY_API_URL' => isset($this->configuration['PS_MBO_PROXY_API_URL']) ? $this->configuration['PS_MBO_PROXY_API_URL'] : null,
                'PS_MBO_SYNC_API_URL' => isset($this->configuration['PS_MBO_SYNC_API_URL']) ? $this->configuration['PS_MBO_SYNC_API_URL'] : null,
            ],
        ];
    }

    public function getContextData(): array
    {
        return [
            'shop_url' => _PS_BASE_URL_,
            'prestashop_version' => _PS_VERSION_,
            'iso_lang' => $this->languageRepository->getDefaultLanguageIsoLang(),
            'iso_code' => $this->languageRepository->getDefaultLanguageIsoCode(),
            'default_currency' => $this->currencyRepository->getDefaultCurrencyIsoCode(),
            'installed_modules' => $this->moduleRepository->getInstalledModules(),
            'multi_shop_count' => $this->shopRepository->getMultiShopCount(),
        ];
    }
}
