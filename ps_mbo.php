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
use PrestaShop\Module\Mbo\Traits\Hooks\UseAdminControllerSetMedia;
use PrestaShop\Module\Mbo\Traits\Hooks\UseAdminModuleInstallRetrieveSource;
use PrestaShop\Module\Mbo\Traits\Hooks\UseAdminModuleUpgradeRetrieveSource;
use PrestaShop\Module\Mbo\Traits\Hooks\UseAdminModuleExtraToolbarButton;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneThree;
use PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneTwo;
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
    use UseDashboardZoneTwo;
    use UseDashboardZoneThree;
    use UseDisplayDashboardTop;
    use UseAdminControllerSetMedia;
    use UseAdminModuleInstallRetrieveSource;
    use UseAdminModuleUpgradeRetrieveSource;
    use UseAdminModuleExtraToolbarButton;
    use UseDisplayBackOfficeFooter;
    use UseDisplayModuleConfigureExtraButtons;

    /**
     * @var array Hooks registered by the module
     */
    public const HOOKS = [
        'actionAdminControllerSetMedia',
        'actionAdminModuleExtraToolbarButton',
        'actionAdminModuleInstallRetrieveSource',
        'actionAdminModuleUpgradeRetrieveSource',
        'displayDashboardTop',
        'displayBackOfficeFooter',
        'displayBackOfficeEmployeeMenu',
        'displayModuleConfigureExtraButtons',
        'dashboardZoneTwo',
        'dashboardZoneThree',
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

        $this->displayName = $this->l('PrestaShop Marketplace in your Back Office');
        $this->description = $this->l('Browse the Addons marketplace directly from your back office to better meet your needs.');

        // Parse all traits to call boot method
        foreach (class_uses($this) as $trait) {
            $traitName = (new UnicodeString($trait))->afterLast('\\')->toString();
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
        return parent::install()
            && $this->registerHook(static::HOOKS);
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
        return parent::enable($force_all)
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
        return parent::disable($force_all)
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
}
