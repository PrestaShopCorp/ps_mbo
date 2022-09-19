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
use PrestaShop\Module\Mbo\Security\PermissionCheckerInterface;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShopBundle\Event\ModuleManagementEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Dotenv\Dotenv;

class ps_mbo extends Module
{
    use PrestaShop\Module\Mbo\Traits\HaveTabs;
    use PrestaShop\Module\Mbo\Traits\UseHooks;
    use PrestaShop\Module\Mbo\Traits\HaveShopOnExternalService;

    public const DEFAULT_ENV = '';

    /**
     * @var string
     */
    public const VERSION = '4.0.0';

    public const CONTROLLERS_WITH_CONNECTION_TOOLBAR = [
        'AdminPsMboModule',
        'AdminModulesManage',
        'AdminModulesSf',
    ];

    public $configurationList = [
        'PS_MBO_SHOP_ADMIN_UUID' => '', // 'ADMIN' because there will be only one for all shops in a multishop context
        'PS_MBO_SHOP_ADMIN_MAIL' => '',
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
     * @var PermissionCheckerInterface
     */
    protected $permissionChecker;

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
        $this->version = self::VERSION;
        $this->author = 'PrestaShop';
        $this->tab = 'administration';
        $this->module_key = '6cad5414354fbef755c7df4ef1ab74eb';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.5.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->imgPath = $this->_path . 'views/img/';
        $this->moduleCacheDir = sprintf('%s/var/modules/%s/', rtrim(_PS_ROOT_DIR_, '/'), $this->name);

        $this->displayName = $this->trans('PrestaShop Marketplace in your Back Office', [], 'Modules.Mbo.Global');
        $this->description = $this->trans('Browse the Addons marketplace directly from your back office to better meet your needs.', [], 'Modules.Mbo.Global');

        if ($this->active) {
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
            // For now, do nothing
        }

        if (parent::install() && $this->registerHook($this->getHooksNames())) {
            // Do come extra operations on modules' registration like modifying orders
            $this->installHooks();

            $this->getAdminAuthenticationProvider()->clearCache();
            $this->getAdminAuthenticationProvider()->createApiUser();

            return true;
        }

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

        $this->getAdminAuthenticationProvider()->deleteApiUser();
        $this->getAdminAuthenticationProvider()->clearCache();

        $registrationLockFile = $this->moduleCacheDir . 'registration.lock';
        if (file_exists($registrationLockFile)) {
            unlink($registrationLockFile);
        }

        foreach (array_keys($this->configurationList) as $name) {
            Configuration::deleteByName($name);
        }

        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->get('event_dispatcher');
        if (!$eventDispatcher->hasListeners(ModuleManagementEvent::UNINSTALL)) {
            return true;
        }

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
        $this->handleTabAction('install');

        // Register online services
        $this->registerShop();

        return true;
    }

    private function enableByShop(int $shopId)
    {
        // Force context to all shops
        Shop::setContext(Shop::CONTEXT_SHOP, $shopId);

        return parent::enable(true);
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

    private function disableByShop(int $shopId)
    {
        // Force context to all shops
        Shop::setContext(Shop::CONTEXT_SHOP, $shopId);

        return parent::disable(true);
    }

    /**
     * Override of native function to always retrieve Symfony container instead of legacy admin container on legacy context.
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
                $this->getLocalPath(),
                $this->getModuleEnv()
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
     * @return void
     */
    private function loadEnv(): void
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__ . '/.env');
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

        return $this->container->has('mbo.security.admin_authentication.provider') ?
                $this->get('mbo.security.admin_authentication.provider') :
                new AdminAuthenticationProvider(
                    $this->get('doctrine.dbal.default_connection'),
                    $this->context,
                    $this->get('prestashop.core.crypto.hashing'),
                    $this->get('doctrine.cache.provider'),
                    $this->container->getParameter('database_prefix')
                );
    }

    private function getModuleEnvVar(): string
    {
        return strtoupper($this->name) . '_ENV';
    }

    private function getModuleEnv(?string $default = null): string
    {
        return getenv($this->getModuleEnvVar()) ?: $default ?: self::DEFAULT_ENV;
    }

    public function getAccountsDataProvider(): AccountsDataProvider
    {
        return $this->getService('mbo.accounts.data_provider');
    }
}
