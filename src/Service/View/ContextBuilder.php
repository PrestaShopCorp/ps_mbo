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

use Doctrine\Common\Cache\CacheProvider;
use PrestaShop\Module\Mbo\Accounts\Provider\AccountsDataProvider;
use PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\Module\Mbo\Helpers\UrlHelper;
use PrestaShop\Module\Mbo\Module\Module;
use PrestaShop\Module\Mbo\Module\ModuleOverrideChecker;
use PrestaShop\Module\Mbo\Module\Workflow\TransitionInterface;
use PrestaShop\PrestaShop\Adapter\Module\Module as CoreModule;
use PrestaShop\PrestaShop\Core\Context\CountryContext;
use PrestaShop\PrestaShop\Core\Context\CurrencyContext;
use PrestaShop\PrestaShop\Core\Context\EmployeeContext;
use PrestaShop\PrestaShop\Core\Context\LanguageContext;
use PrestaShop\PrestaShop\Core\Module\ModuleCollection;
use PrestaShop\PrestaShop\Core\Module\ModuleRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ContextBuilder
{
    public const DEFAULT_CURRENCY_CODE = 'EUR';

    private ModuleCollection $modulesCollection;

    public function __construct(
        private readonly EmployeeContext $employeeContext,
        private readonly CurrencyContext $currencyContext,
        private readonly LanguageContext $languageContext,
        private readonly CountryContext $countryContext,
        private readonly ModuleRepository $moduleRepository,
        private readonly Router $router,
        private readonly CacheProvider $cacheProvider,
        private readonly AdminAuthenticationProvider $adminAuthenticationProvider,
        private readonly AccountsDataProvider $accountsDataProvider,
    ) {
    }

    public function getViewContext(): array
    {
        $context = $this->getCommonContextContent();

        $context['prestaShop_controller_class_name'] = \Tools::getValue('controller');

        return $context;
    }

    public function getRecommendedModulesContext(string $tab): array
    {
        $context = $this->getCommonContextContent();

        $context['prestaShop_controller_class_name'] = $tab;

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
            'iso_lang' => $this->getLanguage(),
            'iso_code' => $this->getCountry(),
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
        $userId = null;
        if ($this->employeeContext->getEmployee()) {
            $userId = $this->employeeContext->getEmployee()->getId();
        }

        $shopActivity = Config::getShopActivity();
        $overrideChecker = ModuleOverrideChecker::getInstance();

        $token = \Tools::getValue('_token');

        if (false === $token) {
            $token = \Tools::getValue('token');
        }

        $mboResetUrl = UrlHelper::transformToAbsoluteUrl(
            $this->router->generate('admin_module_manage_action', [
                'action' => 'reset',
                'module_name' => 'ps_mbo',
            ])
        );

        return [
            'currency' => $this->getCurrencyCode(),
            'iso_lang' => $this->getLanguage(),
            'iso_code' => $this->getCountry(),
            'shop_version' => _PS_VERSION_,
            'shop_url' => Config::getShopUrl(),
            'shop_uuid' => Config::getShopMboUuid(),
            'mbo_token' => $this->adminAuthenticationProvider->getMboJWT(),
            'mbo_version' => \ps_mbo::VERSION,
            'mbo_reset_url' => $mboResetUrl,
            'user_id' => $userId,
            'admin_token' => $token,
            'refresh_url' => '',
            'installed_modules' => $this->getInstalledModules(),
            'upgradable_modules' => $this->getUpgradableModules(),
            'accounts_user_id' => $this->accountsDataProvider->getAccountsUserId(),
            'accounts_shop_id' => $this->accountsDataProvider->getAccountsShopId(),
            'accounts_token' => $this->accountsDataProvider->getAccountsToken() ?: '',
            'accounts_shop_token_v7' => $this->accountsDataProvider->getShopTokenV7(),
            'accounts_shop_token' => $this->accountsDataProvider->getAccountsShopToken(),
            'accounts_component_loaded' => false,
            'module_manager_updates_tab_url' => UrlHelper::transformToAbsoluteUrl($this->router->generate('admin_module_updates')),
            'module_catalog_url' => UrlHelper::transformToAbsoluteUrl($this->router->generate('admin_mbo_catalog_module')),
            'theme_catalog_url' => UrlHelper::transformToAbsoluteUrl($this->router->generate('admin_mbo_catalog_theme')),
            'php_version' => phpversion(),
            'shop_creation_date' => defined('_PS_CREATION_DATE_') ? _PS_CREATION_DATE_ : null,
            'shop_business_sector_id' => $shopActivity['id'],
            'shop_business_sector' => $shopActivity['name'],
            'overrides_on_shop' => $overrideChecker->listOverridesFromPsDirectory(),
            'actions_token' => UrlHelper::getQueryParameterValue($mboResetUrl, '_token'),
            'actions_url' => [
                'install' => $this->generateActionUrl('install'),
                'uninstall' => $this->generateActionUrl('uninstall'),
                'delete' => $this->generateActionUrl('delete'),
                'enable' => $this->generateActionUrl('enable'),
                'disable' => $this->generateActionUrl('disable'),
                'reset' => $this->generateActionUrl('reset'),
                'upgrade' => $this->generateActionUrl('upgrade'),
            ],
        ];
    }

    private function generateActionUrl(string $action): string
    {
        $params = [
            'action' => $action,
            'module_name' => ':module',
        ];

        if (in_array($action, ['install', 'upgrade'])) {
            $params['id'] = '_module_id_';
            $params['source'] = '_download_url_';
        }

        return UrlHelper::transformToAbsoluteUrl($this->router->generate('admin_module_manage_action', $params));
    }

    private function getLanguage(): string
    {
        return $this->languageContext->getIsoCode();
    }

    private function getCountry(): string
    {
        return mb_strtolower($this->countryContext->getIsoCode());
    }

    private function getCurrencyCode(): string
    {
        if (!in_array($this->currencyContext->getIsoCode(), ['EUR', 'USD', 'GBP'])) {
            return self::DEFAULT_CURRENCY_CODE;
        }

        return $this->currencyContext->getIsoCode();
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

        if (empty($this->modulesCollection)) {
            $this->modulesCollection = $this->moduleRepository->getList();
        }

        $installedModules = [];

        /** @var CoreModule $installedModule */
        foreach ($this->modulesCollection as $installedModule) {
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
                $moduleConfigUrl,
                $installedModule->get('download_url'),
            )
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

        if (!empty($this->modulesCollection)) {
            $upgradableModulesCollection = $this->modulesCollection->filter(static function (CoreModule $module) {
                return $module->canBeUpgraded();
            });
        } else {
            $upgradableModulesCollection = $this->moduleRepository->getUpgradableModules();
        }

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
