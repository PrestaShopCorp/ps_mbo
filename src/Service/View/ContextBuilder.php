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

namespace PrestaShop\Module\Mbo\Service\View;

use Configuration;
use Context;
use Country;
use Doctrine\Common\Cache\CacheProvider;
use Language;
use PrestaShop\Module\Mbo\Accounts\Provider\AccountsDataProvider;
use PrestaShop\Module\Mbo\Distribution\AuthenticationProvider;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\PrestaShop\Adapter\LegacyContext as ContextAdapter;
use PrestaShop\PrestaShop\Adapter\Module\Module as CoreModule;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepository;
use Symfony\Component\Routing\Router;
use Tools;

class ContextBuilder
{
    const DEFAULT_CURRENCY_CODE = 'EUR';

    const STATUS_UNINSTALLED = 'uninstalled';
    const STATUS_ENABLED__MOBILE_ENABLED = 'enabled__mobile_enabled';
    const STATUS_ENABLED__MOBILE_DISABLED = 'enabled__mobile_disabled';
    const STATUS_DISABLED__MOBILE_ENABLED = 'disabled__mobile_enabled';
    const STATUS_DISABLED__MOBILE_DISABLED = 'disabled__mobile_disabled';
    const STATUS_RESET = 'reset'; //virtual status
    const STATUS_UPGRADED = 'upgraded'; //virtual status
    const STATUS_CONFIGURED = 'configured'; //virtual status

    /**
     * @var ContextAdapter
     */
    private $contextAdapter;

    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;
    /**
     * @var AuthenticationProvider
     */
    private $distributionAuthenticationProvider;
    /**
     * @var AccountsDataProvider
     */
    private $accountsDataProvider;

    public function __construct(
        ContextAdapter $contextAdapter,
        ModuleRepository $moduleRepository,
        Router $router,
        AuthenticationProvider $distributionAuthenticationProvider,
        CacheProvider $cacheProvider,
        AccountsDataProvider $accountsDataProvider
    ) {
        $this->contextAdapter = $contextAdapter;
        $this->moduleRepository = $moduleRepository;
        $this->router = $router;
        $this->distributionAuthenticationProvider = $distributionAuthenticationProvider;
        $this->cacheProvider = $cacheProvider;
        $this->accountsDataProvider = $accountsDataProvider;
    }

    /**
     * @return array
     */
    public function getViewContext()
    {
        $context = $this->getCommonContextContent();

        $context['prestaShop_controller_class_name'] = Tools::getValue('controller');

        return $context;
    }

    /**
     * @return bool
     */
    public function clearCache()
    {
        $cacheKey = $this->getCacheKey();

        if ($this->cacheProvider->contains($cacheKey)) {
            if (!$this->cacheProvider->delete($cacheKey)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    private function getCommonContextContent()
    {
        $context = $this->getContext();
        $language = $this->getLanguage();
        $country = $this->getCountry();
        $psMbo = \Module::getInstanceByName('ps_mbo');

        $psMboVersion = false;
        if (\Validate::isLoadedObject($psMbo)) {
            $psMboVersion = $psMbo->version;
        }

        $token = Tools::getValue('_token');

        if (false === $token) {
            $token = Tools::getValue('token');
        }

        $refreshUrl = $this->router->generate('admin_mbo_security');

        $shopUuid = Config::getShopMboUuid();
        $shopActivity = Config::getShopActivity();

        return [
            'currency' => $this->getCurrencyCode(),
            'iso_lang' => $language->iso_code,
            'iso_code' => mb_strtolower($country->iso_code),
            'shop_version' => _PS_VERSION_,
            'shop_url' => Config::getShopUrl(),
            'shop_uuid' => $shopUuid,
            'mbo_token' => $this->distributionAuthenticationProvider->getMboJWT(),
            'mbo_version' => $psMboVersion,
            'mbo_reset_url' => $this->router->generate('admin_module_manage_action', [
                'action' => 'reset',
                'module_name' => 'ps_mbo',
            ]),
            'user_id' => $context->cookie->id_employee,
            'admin_token' => $token,
            'refresh_url' => $refreshUrl,
            'installed_modules' => $this->getInstalledModules(),
            'accounts_user_id' => $this->accountsDataProvider->getAccountsUserId(),
            'accounts_shop_id' => $this->accountsDataProvider->getAccountsShopId(),
            'accounts_token' => $this->accountsDataProvider->getAccountsToken(),
            'accounts_component_loaded' => false,
            'module_catalog_url' => $this->router->generate('admin_mbo_catalog_module'),
            'theme_catalog_url' => $this->router->generate('admin_mbo_catalog_theme'),
            'php_version' => phpversion(),
            'shop_creation_date' => defined('_PS_CREATION_DATE_') ? _PS_CREATION_DATE_ : null,
            'shop_business_sector_id' => $shopActivity['id'],
            'shop_business_sector' => $shopActivity['name'],
        ];
    }

    /**
     * @return Context|null
     */
    private function getContext()
    {
        return $this->contextAdapter->getContext();
    }

    /**
     * @return Language
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function getLanguage()
    {
        return null !== $this->getContext() ? $this->getContext()->language : new Language((int) Configuration::get('PS_LANG_DEFAULT'));
    }

    /**
     * @return Country
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function getCountry()
    {
        return null !== $this->getContext() ? $this->getContext()->country : new Country((int) Configuration::get('PS_COUNTRY_DEFAULT'));
    }

    /**
     * @return string
     */
    private function getCurrencyCode()
    {
        if (null === $this->getContext()) {
            return self::DEFAULT_CURRENCY_CODE;
        }

        $currency = $this->getContext()->currency;

        if (\Validate::isLoadedObject($currency) || !in_array($currency->iso_code, ['EUR', 'USD', 'GBP'])) {
            return self::DEFAULT_CURRENCY_CODE;
        }

        return $currency->iso_code;
    }

    /**
     * @return array<array>
     */
    private function getInstalledModules()
    {
        $cacheKey = $this->getCacheKey();

        if ($this->cacheProvider->contains($cacheKey)) {
            return $this->cacheProvider->fetch($cacheKey);
        }

        $installedModulesCollection = $this->moduleRepository->getInstalledModulesCollection();

        $installedModules = [];

        /** @var CoreModule $installedModule */
        foreach ($installedModulesCollection as $installedModule) {
            $moduleAttributes = $installedModule->attributes;
            $moduleDiskAttributes = $installedModule->disk;
            $moduleDatabaseAttributes = $installedModule->database;

            $module = $installedModule;

            $moduleId = (int) $module->get('id');
            $moduleName = $module->get('name');
            $moduleStatus = $this->getModuleStatus($module);
            $moduleVersion = $module->get('version');
            $moduleConfigUrl = null;

            if (!$moduleName || !$moduleVersion) {
                continue;
            }

            if (true === (bool) $module->get('is_configurable')) {
                $moduleConfigUrl = $this->router->generate('admin_module_configure_action', [
                    'module_name' => $moduleName,
                ]);
            }

            $installedModules[] = (new InstalledModule($moduleId, $moduleName, $moduleStatus, (string) $moduleVersion, $moduleConfigUrl))->toArray();
        }

        $this->cacheProvider->save($cacheKey, $installedModules, 86400); // Lifetime for 24h, will be purged at every action on modules

        return $this->cacheProvider->fetch($cacheKey);
    }

    /**
     * @return string
     */
    private function getCacheKey()
    {
        return sprintf('mbo_installed_modules_list_%s', Config::getShopMboUuid());
    }

    /**
     * @param CoreModule $module
     *
     * @return string
     */
    private function getModuleStatus(CoreModule $module)
    {
        $installed = (bool) $module->database->get('installed');
        $active = (bool) $module->database->get('active');
        $activeOnMobile = (bool) $module->database->get('active_on_mobile');

        if (!$installed) {
            return self::STATUS_UNINSTALLED;
        }

        if ($active && $activeOnMobile) {
            return self::STATUS_ENABLED__MOBILE_ENABLED;
        }

        if ($active && !$activeOnMobile) {
            return self::STATUS_ENABLED__MOBILE_DISABLED;
        }

        if (!$active && $activeOnMobile) {
            return self::STATUS_DISABLED__MOBILE_ENABLED;
        }

        return self::STATUS_DISABLED__MOBILE_DISABLED;
    }
}
