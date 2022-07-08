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

use Dotenv\Dotenv;
use PrestaShop\Module\Mbo\Addons\Subscriber\ModuleManagementEventSubscriber;
use PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider;
use PrestaShop\Module\Mbo\Security\PermissionCheckerInterface;
use PrestaShop\Module\Mbo\Traits\Hooks\UseAdminControllerSetMedia;
use PrestaShop\Module\Mbo\Traits\Hooks\UseBeforeInstallModule;
use PrestaShop\Module\Mbo\Traits\Hooks\UseBeforeUpgradeModule;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneOne;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneThree;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneTwo;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDispatcherBefore;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayAdminThemesListAfter;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayBackOfficeEmployeeMenu;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayBackOfficeFooter;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayDashboardTop;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayEmptyModuleCategoryExtraMessage;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayModuleConfigureExtraButtons;
use PrestaShop\Module\Mbo\Traits\Hooks\UseGetAdminToolbarButtons;
use PrestaShop\Module\Mbo\Traits\Hooks\UseGetAlternativeSearchPanels;
use PrestaShop\Module\Mbo\Traits\Hooks\UseListModules;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShopBundle\Event\ModuleManagementEvent;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\String\UnicodeString;

class ps_mbo extends Module
{
    use PrestaShop\Module\Mbo\Traits\HaveTabs;
    // Hooks
    use UseDisplayBackOfficeEmployeeMenu;
    use UseDashboardZoneOne;
    use UseDisplayAdminThemesListAfter;
    use UseDashboardZoneTwo;
    use UseDashboardZoneThree;
    use UseDisplayDashboardTop;
    use UseAdminControllerSetMedia;
    use UseBeforeInstallModule;
    use UseDispatcherBefore;
    use UseBeforeUpgradeModule;
    use UseGetAdminToolbarButtons;
    use UseGetAlternativeSearchPanels;
    use UseDisplayBackOfficeFooter;
    use UseDisplayModuleConfigureExtraButtons;
    use UseListModules;
    use UseDisplayEmptyModuleCategoryExtraMessage;

    /**
     * @var string
     */
    const VERSION = '4.0.0';

    /**
     * @var array Hooks registered by the module
     */
    public const HOOKS = [
        'actionAdminControllerSetMedia',
        'actionGetAdminToolbarButtons',
        'actionGetAlternativeSearchPanels',
        'actionBeforeInstallModule',
        'actionBeforeUpgradeModule',
        'actionDispatcherBefore',
        'actionListModules',
        'displayAdminThemesListAfter',
        'displayDashboardTop',
        'displayBackOfficeFooter',
        'displayBackOfficeEmployeeMenu',
        'displayEmptyModuleCategoryExtraMessage',
        'displayModuleConfigureExtraButtons',
        'dashboardZoneOne',
        'dashboardZoneTwo',
        'dashboardZoneThree',
    ];

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
     * @var array An array of method that can be called to register media in the actionAdminControllerSetMedia hook
     *
     * @see UseAdminControllerSetMedia
     */
    protected $adminControllerMediaMethods = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PermissionCheckerInterface
     */
    protected $permissionChecker;

    /**
     * @var string
     */
    public $imgPath;

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

        $this->displayName = $this->trans('PrestaShop Marketplace in your Back Office', [], 'Modules.Mbo.Global');
        $this->description = $this->trans('Browse the Addons marketplace directly from your back office to better meet your needs.', [], 'Modules.Mbo.Global');

        // Parse all traits to call boot method
        foreach ($this->getTraitNames() as $traitName) {
            if (method_exists($this, "boot{$traitName}")) {
                $this->{"boot{$traitName}"}();
            }
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
        if (parent::install() && $this->installConfiguration() && $this->registerHook(static::HOOKS)) {
            // Do come extra operations on modules' registration like modifying orders
            foreach ($this->getTraitNames() as $traitName) {
                $traitName = lcfirst($traitName);
                if (method_exists($this, "{$traitName}ExtraOperations")) {
                    $this->{"{$traitName}ExtraOperations"}();
                }
            }

            $this->getAdminAuthenticationProvider()->createApiUser();

            return true;
        }

        return false;
    }

    /**
     * Install configuration for each shop
     *
     * @return bool
     */
    public function installConfiguration(): bool
    {
        $result = true;

        // Values generated
        $adminUuid = Uuid::uuid4();
        $this->configurationList['PS_MBO_SHOP_ADMIN_UUID'] = $adminUuid;
        $this->configurationList['PS_MBO_SHOP_ADMIN_MAIL'] = sprintf('mbo-%s@prestashop.com', $adminUuid);

        foreach (\Shop::getShops(false, null, true) as $shopId) {
            foreach ($this->configurationList as $name => $value) {
                if (false === Configuration::hasKey($name, null, null, (int) $shopId)) {
                    $result = $result && (bool) Configuration::updateValue(
                            $name,
                            $value,
                            false,
                            null,
                            (int) $shopId
                        );
                }
            }
        }

        return $result;
    }

    /**
     * @inerhitDoc
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
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

        $this->getAdminAuthenticationProvider()->deleteApiUser();

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

        return $this->handleTabAction('install');
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

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    protected function getTraitNames(): array
    {
        $traits = [];
        foreach (class_uses($this) as $trait) {
            $traits[] = (new UnicodeString($trait))->afterLast('\\')->toString();
        }

        return $traits;
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

    private function loadEnv()
    {
        if (file_exists(_PS_MODULE_DIR_ . 'ps_mbo/.env.dist')) {
            $dotenv = Dotenv::createUnsafeImmutable(_PS_MODULE_DIR_ . 'ps_mbo/', '.env.dist');
            $dotenv->load();
        }

        if (file_exists(_PS_MODULE_DIR_ . 'ps_mbo/.env')) {
            $dotenv = Dotenv::createUnsafeImmutable(_PS_MODULE_DIR_ . 'ps_mbo/');
            $dotenv->load();
        }
    }

    private function getAdminAuthenticationProvider(): AdminAuthenticationProvider
    {
        return $this->get('PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider');
    }
}
