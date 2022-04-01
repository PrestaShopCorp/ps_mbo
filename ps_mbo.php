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

use PrestaShop\Module\Mbo\Security\PermissionCheckerInterface;
use PrestaShop\Module\Mbo\Traits\Hooks\UseActionPresentModule;
use PrestaShop\Module\Mbo\Traits\Hooks\UseAdminControllerSetMedia;
use PrestaShop\Module\Mbo\Traits\Hooks\UseAdminModuleExtraToolbarButton;
use PrestaShop\Module\Mbo\Traits\Hooks\UseAdminModuleInstallRetrieveSource;
use PrestaShop\Module\Mbo\Traits\Hooks\UseAdminModuleUpgradeRetrieveSource;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneOne;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneThree;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneTwo;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayAdminThemesListAfter;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayBackOfficeEmployeeMenu;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayBackOfficeFooter;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayDashboardTop;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayModuleConfigureExtraButtons;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
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
    use UseAdminModuleInstallRetrieveSource;
    use UseAdminModuleUpgradeRetrieveSource;
    use UseAdminModuleExtraToolbarButton;
    use UseDisplayBackOfficeFooter;
    use UseDisplayModuleConfigureExtraButtons;
    use UseActionPresentModule;

    /**
     * @var array Hooks registered by the module
     */
    public const HOOKS = [
        'actionAdminControllerSetMedia',
        'actionAdminModuleExtraToolbarButton',
        'actionAdminModuleInstallRetrieveSource',
        'actionAdminModuleUpgradeRetrieveSource',
        'displayAdminThemesListAfter',
        'displayDashboardTop',
        'displayBackOfficeFooter',
        'displayBackOfficeEmployeeMenu',
        'displayModuleConfigureExtraButtons',
        'dashboardZoneOne',
        'dashboardZoneTwo',
        'dashboardZoneThree',
        'actionPresentModule',
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
        $this->version = '2.0.2';
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
    }

    /**
     * Install Module.
     *
     * @return bool
     */
    public function install(): bool
    {
        if (parent::install() && $this->registerHook(static::HOOKS)) {
            // Do come extra operations on modules' registration like modifying orders
            foreach ($this->getTraitNames() as $traitName) {
                $traitName = lcfirst($traitName);
                if (method_exists($this, "{$traitName}ExtraOperations")) {
                    $this->{"{$traitName}ExtraOperations"}();
                }
            }

            return true;
        }

        return false;
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
        return parent::enable(true)
            && $this->handleTabAction('install');
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
        return parent::disable(true)
            && $this->handleTabAction('uninstall');
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
}
