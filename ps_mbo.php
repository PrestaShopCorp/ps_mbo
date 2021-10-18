<?php
/**
 * 2007-2021 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
declare(strict_types=1);
if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ps_mbo extends Module
{
    use PrestaShop\Module\Mbo\Traits\UseHooks;
    use PrestaShop\Module\Mbo\Traits\HaveTabs;

    public const HOOKS = [
        'actionAdminControllerSetMedia',
        'displayDashboardTop',
        'displayBackOfficeEmployeeMenu',
        'dashboardZoneTwo',
        'dashboardZoneThree',
    ];
    /**
     * @var ContainerInterface
     */
    protected $container;

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
