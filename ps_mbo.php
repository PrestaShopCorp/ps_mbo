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
if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

use PrestaShop\Module\Mbo\Accounts\Provider\AccountsDataProvider;
use PrestaShop\Module\Mbo\Addons\Subscriber\ModuleManagementEventSubscriber;
use PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShopBundle\Event\ModuleManagementEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Dotenv\Dotenv;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;

class ps_mbo extends Module
{
    use PrestaShop\Module\Mbo\Traits\HaveTabs;
    use PrestaShop\Module\Mbo\Traits\UseHooks;
    use PrestaShop\Module\Mbo\Traits\HaveShopOnExternalService;
    use PrestaShop\Module\Mbo\Traits\HaveConfigurationPage;

    /**
     * @var string
     */
    public const VERSION = '4.11.4';

    public const CONTROLLERS_WITH_CONNECTION_TOOLBAR = [
        'AdminModulesManage',
        'AdminModulesSf',
    ];

    public const CONTROLLERS_WITH_CDC_SCRIPT = [
        'AdminModulesNotifications',
        'AdminModulesUpdates',
        'AdminModulesManage',
    ];

    public $configurationList = [
        'PS_MBO_SHOP_ADMIN_UUID' => '', // 'ADMIN' because there will be only one for all shops in a multishop context
        'PS_MBO_SHOP_ADMIN_MAIL' => '',
        'PS_MBO_LAST_PS_VERSION_API_CONFIG' => '',
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \PrestaShop\Module\Mbo\DependencyInjection\ServiceContainer
     */
    private $serviceContainer;

    /**
     * @var string
     */
    public $imgPath;

    /**
     * @var string
     */
    public $moduleCacheDir;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = 'ps_mbo';
        $this->version = '4.11.4';
        $this->author = 'PrestaShop';
        $this->tab = 'administration';
        $this->module_key = '6cad5414354fbef755c7df4ef1ab74eb';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.2',
            'max' => '8.99.99',
        ];

        parent::__construct();

        $this->imgPath = $this->_path . 'views/img/';
        $this->moduleCacheDir = sprintf('%s/var/modules/%s/', rtrim(_PS_ROOT_DIR_, '/'), $this->name);

        $this->displayName = $this->trans('PrestaShop Marketplace in your Back Office', [], 'Modules.Mbo.Global');
        $this->description = $this
            ->trans('Browse the Addons marketplace directly from your back office to better meet your needs.',
                [],
                'Modules.Mbo.Global'
            );

        if (self::checkModuleStatus()) {
            $this->bootHooks();
        }

        $this->loadEnv();
    }

    /**
     * Install Module.
     *
     * @return bool
     */
    public function install(): bool
    {
        try {
            $this->getService('mbo.ps_accounts.installer')->install();
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
        }

        try {
            $this->getService('mbo.ps_eventbus.installer')->install();
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
        }

        $this->installTables();
        if (parent::install() && $this->registerHook($this->getHooksNames())) {
            // Do come extra operations on modules' registration like modifying orders
            $this->installHooks();

            $this->getAdminAuthenticationProvider()->clearCache();
            $this->getAdminAuthenticationProvider()->createApiUser();
            $this->postponeTabsTranslations();

            return true;
        }

        // If installation fails, we remove the tables previously created
        $this->uninstallTables();

        return false;
    }

    /**
     * @inerhitDoc
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        $this->getAdminAuthenticationProvider()->deletePossibleApiUser();
        $this->getAdminAuthenticationProvider()->clearCache();

        $lockFiles = ['registerShop', 'updateShop', 'createApiUser'];
        foreach ($lockFiles as $lockFile) {
            if (file_exists($this->moduleCacheDir . $lockFile . '.lock')) {
                unlink($this->moduleCacheDir . $lockFile . '.lock');
            }
        }

        foreach (array_keys($this->configurationList) as $name) {
            Configuration::deleteByName($name);
        }

        // This will reset cached configuration values (uuid, mail, ...) to avoid reusing them
        Config::resetConfigValues();

        $this->uninstallTables();

        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->get('event_dispatcher');
        if (!$eventDispatcher->hasListeners(ModuleManagementEvent::UNINSTALL)) {
            return true;
        }

        // Execute them first
        foreach ($eventDispatcher->getListeners(ModuleManagementEvent::UNINSTALL) as $listener) {
            if ($listener[0] instanceof ModuleManagementEventSubscriber) {
                $legacyModule = $this->get('prestashop.core.admin.module.repository')->getModule('ps_mbo');
                $listener[0]->{(string)$listener[1]}(new ModuleManagementEvent($legacyModule));
            }
        }

        //And then remove them
        foreach ($eventDispatcher->getListeners(ModuleManagementEvent::UNINSTALL) as $listener) {
            if ($listener[0] instanceof ModuleManagementEventSubscriber) {
                $eventDispatcher->removeSubscriber($listener[0]);
                break;
            }
        }

        return true;
    }

    /**
     * Enable Module.
     *
     * @param bool $force_all
     *
     * @return bool
     */
    public function enable($force_all = false): bool
    {
        if (self::checkModuleStatus()) {
            return true;
        }
        // Store previous context
        $previousContextType = Shop::getContext();
        $previousContextShopId = Shop::getContextShopID();

        $allShops = Shop::getShops(true, null, true);

        foreach ($allShops as $shop) {
            if (!$this->enableByShop($shop)) {
                return false;
            }
        }

        // Restore previous context
        Shop::setContext($previousContextType, $previousContextShopId);

        // Install tab before registering shop, we need the tab to be active to create the good token
        $this->updateTabs();
        $this->postponeTabsTranslations();

        // Register online services
        $this->registerShop();

        return true;
    }

    /**
     * Disable Module.
     *
     * @param bool $force_all
     *
     * @return bool
     */
    public function disable($force_all = false): bool
    {
        // Store previous context
        $previousContextType = Shop::getContext();
        $previousContextShopId = Shop::getContextShopID();

        $allShops = Shop::getShops(true, null, true);

        foreach ($allShops as $shop) {
            if (!$this->disableByShop($shop)) {
                return false;
            }
        }

        // Restore previous context
        Shop::setContext($previousContextType, $previousContextShopId);

        // Unregister from online services
        $this->unregisterShop();

        return $this->handleTabAction('uninstall');
    }

    /**
     * Override of native function to always retrieve Symfony container instead of legacy admin
     * container on legacy context.
     *
     * {@inheritdoc}
     */
    public function get($serviceName)
    {
        if (null === $this->container) {
            $this->container = SymfonyContainer::getInstance();
        }

        return $this->container->get($serviceName);
    }

    /**
     * @param string $serviceName
     *
     * @return object|null
     */
    public function getService($serviceName)
    {
        if ($this->serviceContainer === null) {
            $this->serviceContainer = new \PrestaShop\Module\Mbo\DependencyInjection\ServiceContainer(
                $this->name . str_replace('.', '', $this->version),
                $this->getLocalPath()
            );
        }

        return $this->serviceContainer->getService($serviceName);
    }

    /**
     * @inerhitDoc
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    /**
     * Used to correctly check if the module is enabled or not whe registering services
     *
     * @return bool
     */
    public static function checkModuleStatus(): bool
    {
        // First if the module have active=0 in the DB, the module is inactive
        $result = Db::getInstance()->getRow('SELECT `active`
        FROM `' . _DB_PREFIX_ . 'module`
        WHERE `name` = "ps_mbo"');
        if ($result && false === (bool) $result['active']) {
            return false;
        }

        // If active = 1
        //in the module table, the module must be associated to at least one shop to be considered as active
        $result = Db::getInstance()->getRow('SELECT m.`id_module` as `active`, ms.`id_module` as `shop_active`
        FROM `' . _DB_PREFIX_ . 'module` m
        LEFT JOIN `' . _DB_PREFIX_ . 'module_shop` ms ON m.`id_module` = ms.`id_module`
        WHERE `name` = "ps_mbo"');
        if ($result) {
            return $result['active'] && $result['shop_active'];
        } else {
            return false;
        }
    }

    /**
     * Get an existing or build an instance of AdminAuthenticationProvider
     *
     * @return \PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider
     *
     * @throws \Exception
     */
    public function getAdminAuthenticationProvider(): AdminAuthenticationProvider
    {
        if (null === $this->container) {
            $this->container = SymfonyContainer::getInstance();
        }

        return null !== $this->container && $this->container->has('mbo.security.admin_authentication.provider') ?
            $this->get('mbo.security.admin_authentication.provider') :
            new AdminAuthenticationProvider(
                $this->get('doctrine.dbal.default_connection'),
                $this->context,
                $this->get('prestashop.core.crypto.hashing'),
                $this->get('doctrine.cache.provider'),
                $this->container->getParameter('database_prefix')
            );
    }

    public function installTables(?string $table = null): bool
    {
        $sqlQueries = [];

        if (null === $table || 'mbo_api_config' === $table) {
            $sqlQueries[] = ' CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mbo_api_config` (
                `id_mbo_api_config` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `config_key` varchar(255) NULL,
                `config_value` varchar(255) NULL,
                `ps_version` varchar(255) NULL,
                `mbo_version` varchar(255) NULL,
                `applied` TINYINT(1) NOT NULL DEFAULT \'0\',
                `date_add` datetime NOT NULL,
                PRIMARY KEY (`id_mbo_api_config`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;';
        }

        foreach ($sqlQueries as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    public function getAccountsDataProvider(): ?AccountsDataProvider
    {
        try {
            return $this->getService('mbo.accounts.data_provider');
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);
            return null;
        }
    }

    public function postponeTabsTranslations(): void
    {
        /**it'
         * There is an issue for translating tabs during installation :
         * Active modules translations files are loaded during the kernel boot.
         * So the installing module translations are not known
         * So, we postpone the tabs translations for the first time the module's code is executed.
         */
        $lockFile = $this->moduleCacheDir . 'translate_tabs.lock';
        if (!file_exists($lockFile)) {
            if (!is_dir($this->moduleCacheDir)) {
                mkdir($this->moduleCacheDir, 0777, true);
            }
            $f = fopen($lockFile, 'w+');
            fclose($f);
        }
    }

    private function enableByShop(int $shopId)
    {
        // Force context to all shops
        Shop::setContext(Shop::CONTEXT_SHOP, $shopId);

        return parent::enable(true);
    }

    private function disableByShop(int $shopId)
    {
        // Force context to all shops
        Shop::setContext(Shop::CONTEXT_SHOP, $shopId);

        return parent::disable(true);
    }

    private function uninstallTables(): bool
    {
        $sqlQueries[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mbo_api_config`';

        foreach ($sqlQueries as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return void
     */
    private function loadEnv(): void
    {
        $dotenv = new Dotenv(true);
        $dotenv->loadEnv(__DIR__ . '/.env');
    }

    private function isPsAccountEnabled(): bool
    {
        $accountsInstaller = $this->get('mbo.ps_accounts.installer');

        return null !== $accountsInstaller && $accountsInstaller->isModuleEnabled();
    }
}
