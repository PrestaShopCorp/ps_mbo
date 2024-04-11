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
use Country;
use Doctrine\Common\Cache\CacheProvider;
use Language;
use PrestaShop\Module\Mbo\Accounts\Provider\AccountsDataProvider;
use PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\Module\Mbo\Helpers\UrlHelper;
use PrestaShop\Module\Mbo\Module\Module;
use PrestaShop\Module\Mbo\Module\Workflow\TransitionInterface;
use PrestaShop\Module\Mbo\Tab\Tab;
use PrestaShop\PrestaShop\Adapter\LegacyContext as ContextAdapter;
use PrestaShop\PrestaShop\Adapter\Module\Module as CoreModule;
use PrestaShop\PrestaShop\Core\Module\ModuleRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Tools;

class ContextBuilder
{
    public const DEFAULT_CURRENCY_CODE = 'EUR';

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
     * @var AdminAuthenticationProvider
     */
    private $adminAuthenticationProvider;

    /**
     * @var AccountsDataProvider
     */
    private $accountsDataProvider;

    public function __construct(
        ContextAdapter $contextAdapter,
        ModuleRepository $moduleRepository,
        Router $router,
        CacheProvider $cacheProvider,
        AdminAuthenticationProvider $adminAuthenticationProvider,
        AccountsDataProvider $accountsDataProvider
    ) {
        $this->contextAdapter = $contextAdapter;
        $this->moduleRepository = $moduleRepository;
        $this->router = $router;
        $this->cacheProvider = $cacheProvider;
        $this->adminAuthenticationProvider = $adminAuthenticationProvider;
        $this->accountsDataProvider = $accountsDataProvider;
    }

    public function getViewContext(): array
    {
        $context = $this->getCommonContextContent();

        $context['prestaShop_controller_class_name'] = Tools::getValue('controller');

        return $context;
    }

    public function getRecommendedModulesContext(Tab $tab): array
    {
        $context = $this->getCommonContextContent();

        $context['prestaShop_controller_class_name'] = $tab->getLegacyClassName();

        return $context;
    }

    public function getEventContext(): array
    {
        $modules = [];
        // Filter : remove uninstalled modules
        foreach ($this->listInstalledModulesAndStatuses() as $installedModule) {
            if ($installedModule['status'] !== TransitionInterface::STATUS_UNINSTALLED) {
                $modules[] = $installedModule['name'];
            }
        }

        $shopActivity = Config::getShopActivity();

        return [
            'modules' => $modules,
            'user_id' => $this->accountsDataProvider->getAccountsUserId(),
            'shop_id' => $this->accountsDataProvider->getAccountsShopId(),
            'accounts_token' => $this->accountsDataProvider->getAccountsToken(),
            'iso_lang' => $this->getLanguage()->getIsoCode(),
            'iso_code' => $this->getCountry()->iso_code,
            'mbo_version' => \ps_mbo::VERSION,
            'ps_version' => _PS_VERSION_,
            'shop_url' => Config::getShopUrl(),
            'shop_creation_date' => defined('_PS_CREATION_DATE_') ? _PS_CREATION_DATE_ : null,
            'shop_business_sector_id' => $shopActivity['id'],
            'shop_business_sector' => $shopActivity['name'],
        ];
    }

    public function clearCache(): bool
    {
        $installedModulesCacheKey = $this->getInstalledModulesCacheKey();
        $upgradableModulesCacheKey = $this->getUpgradableModulesCacheKey();

        if (
            $this->cacheProvider->contains($installedModulesCacheKey)
            && !$this->cacheProvider->delete($installedModulesCacheKey)
        ) {
            return false;
        }

        if (
            $this->cacheProvider->contains($upgradableModulesCacheKey)
            && !$this->cacheProvider->delete($upgradableModulesCacheKey)
        ) {
            return false;
        }

        return true;
    }

    private function getCommonContextContent(): array
    {
        $context = $this->getContext();
        $language = $this->getLanguage();
        $country = $this->getCountry();
        $shopActivity = Config::getShopActivity();

        $token = Tools::getValue('_token');

        if (false === $token) {
            $token = Tools::getValue('token');
        }

        $refreshUrl = Context::getContext()->link->getAdminLink('apiSecurityPsMbo');

        return [
            'currency' => $this->getCurrencyCode(),
            'iso_lang' => $language->getIsoCode(),
            'iso_code' => mb_strtolower($country->iso_code),
            'shop_version' => _PS_VERSION_,
            'shop_url' => Config::getShopUrl(),
            'shop_uuid' => Config::getShopMboUuid(),
            'mbo_token' => $this->adminAuthenticationProvider->getMboJWT(),
            'mbo_version' => \ps_mbo::VERSION,
            'mbo_reset_url' => UrlHelper::transformToAbsoluteUrl(
                $this->router->generate('admin_module_manage_action', [
                    'action' => 'reset',
                    'module_name' => 'ps_mbo',
                ])
            ),
            'user_id' => $context->cookie->id_employee,
            'admin_token' => $token,
            'refresh_url' => $refreshUrl,
            'installed_modules' => $this->getInstalledModules(),
            'upgradable_modules' => $this->getUpgradableModules(),
            'accounts_user_id' => $this->accountsDataProvider->getAccountsUserId(),
            'accounts_shop_id' => $this->accountsDataProvider->getAccountsShopId(),
            'accounts_token' => $this->accountsDataProvider->getAccountsToken(),
            'accounts_component_loaded' => false,
            'module_manager_updates_tab_url' => UrlHelper::transformToAbsoluteUrl($this->router->generate('admin_module_updates')),
            'module_catalog_url' => UrlHelper::transformToAbsoluteUrl($this->router->generate('admin_mbo_catalog_module')),
            'theme_catalog_url' => UrlHelper::transformToAbsoluteUrl($this->router->generate('admin_mbo_catalog_theme')),
            'php_version' => phpversion(),
            'shop_creation_date' => defined('_PS_CREATION_DATE_') ? _PS_CREATION_DATE_ : null,
            'shop_business_sector_id' => $shopActivity['id'],
            'shop_business_sector' => $shopActivity['name'],
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

    private function getCountry(): Country
    {
        return $this->getContext()->country ?? new Country((int) Configuration::get('PS_COUNTRY_DEFAULT'));
    }

    private function getCurrencyCode(): string
    {
        $currency = $this->getContext()->currency;

        if (null === $currency || !in_array($currency->iso_code, ['EUR', 'USD', 'GBP'])) {
            return self::DEFAULT_CURRENCY_CODE;
        }

        return $currency->iso_code;
    }

    /**
     * @return array<array>
     */
    private function listInstalledModulesAndStatuses(): array
    {
        $installedModulesCollection = $this->moduleRepository->getList();

        $installedModules = [];

        /** @var CoreModule $installedModule */
        foreach ($installedModulesCollection as $installedModule) {
            $moduleAttributes = $installedModule->getAttributes();
            $moduleDiskAttributes = $installedModule->getDiskAttributes();
            $moduleDatabaseAttributes = $installedModule->getDatabaseAttributes();

            $module = new Module($moduleAttributes->all(), $moduleDiskAttributes->all(), $moduleDatabaseAttributes->all());

            $moduleName = $module->get('name');
            $moduleStatus = $module->getStatus();

            $installedModules[] = [
                'name' => $moduleName,
                'status' => $moduleStatus,
            ];
        }

        return $installedModules;
    }

    /**
     * @return array<array>
     */
    private function getInstalledModules(): array
    {
        $cacheKey = $this->getInstalledModulesCacheKey();

        if ($this->cacheProvider->contains($cacheKey)) {
            return $this->cacheProvider->fetch($cacheKey);
        }

        $installedModulesCollection = $this->moduleRepository->getList();

        $installedModules = [];

        /** @var CoreModule $installedModule */
        foreach ($installedModulesCollection as $installedModule) {
            $moduleAttributes = $installedModule->getAttributes();
            $moduleDiskAttributes = $installedModule->getDiskAttributes();
            $moduleDatabaseAttributes = $installedModule->getDatabaseAttributes();

            $module = new Module(
                $moduleAttributes->all(),
                $moduleDiskAttributes->all(),
                $moduleDatabaseAttributes->all()
            );

            $moduleId = (int) $moduleAttributes->get('id');
            $moduleName = $module->get('name');
            $moduleStatus = $module->getStatus();
            $moduleVersion = $module->get('version');
            $moduleConfigUrl = null;

            if (!$moduleName || !$moduleVersion || !$moduleStatus) {
                continue;
            }

            if ($installedModule->isConfigurable()) {
                $moduleConfigUrl = UrlHelper::transformToAbsoluteUrl(
                    $this->router->generate(
                        'admin_module_configure_action',
                        [
                        'module_name' => $moduleName,
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                );
            }
            $installedModules[] = (new InstalledModule(
                $moduleId,
                $moduleName,
                $moduleStatus,
                (string) $moduleVersion,
                $moduleConfigUrl)
            )->toArray();
        }

        // Lifetime for 24h, will be purged at every action on modules
        $this->cacheProvider->save($cacheKey, $installedModules, 86400);

        return $this->cacheProvider->fetch($cacheKey);
    }

    private function getInstalledModulesCacheKey(): string
    {
        return sprintf('mbo_installed_modules_list_%s', Config::getShopMboUuid());
    }

    private function getUpgradableModulesCacheKey(): string
    {
        return sprintf('mbo_upgradable_modules_list_%s', Config::getShopMboUuid());
    }

    /**
     * @return array<array>
     */
    private function getUpgradableModules(): array
    {
        $cacheKey = $this->getUpgradableModulesCacheKey();

        if ($this->cacheProvider->contains($cacheKey)) {
            return $this->cacheProvider->fetch($cacheKey);
        }

        $upgradableModulesCollection = $this->moduleRepository->getUpgradableModules();

        $upgradableModules = [];

        /** @var CoreModule $upgradableModule */
        foreach ($upgradableModulesCollection as $upgradableModule) {
            $moduleAttributes = $upgradableModule->getAttributes();

            $moduleName = $moduleAttributes->get('name');

            if (!$moduleName) {
                continue;
            }

            $upgradableModules[] = $moduleName;
        }

        // Lifetime for 24h, will be purged at every action on modules
        $this->cacheProvider->save($cacheKey, $upgradableModules, 86400);

        return $this->cacheProvider->fetch($cacheKey);
    }
}
