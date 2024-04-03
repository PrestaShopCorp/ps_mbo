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

namespace PrestaShop\Module\Mbo\Addons\Subscriber;

use PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider;
use PrestaShop\Module\Mbo\Distribution\Client;
use PrestaShop\Module\Mbo\Distribution\Config\Command\VersionChangeApplyConfigCommand;
use PrestaShop\Module\Mbo\Distribution\Config\CommandHandler\VersionChangeApplyConfigCommandHandler;
use PrestaShop\Module\Mbo\Module\Module;
use PrestaShop\Module\Mbo\Module\Repository;
use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use PrestaShop\Module\Mbo\Tab\TabCollectionProviderInterface;
use PrestaShop\PrestaShop\Adapter\Cache\Clearer\SymfonyCacheClearer;
use PrestaShop\PrestaShop\Core\Cache\Clearer\CacheClearerInterface;
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;
use PrestaShopBundle\Event\ModuleManagementEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Performs actions on Module lifecycle events
 */
class ModuleManagementEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Repository
     */
    protected $moduleRepository;
    /**
     * @var TabCollectionProviderInterface
     */
    protected $tabCollectionProvider;
    /**
     * @var ContextBuilder
     */
    private $contextBuilder;

    /**
     * @var Client
     */
    private $distributionClient;

    /**
     * @var AdminAuthenticationProvider
     */
    private $adminAuthenticationProvider;

    /**
     * @var VersionChangeApplyConfigCommandHandler
     */
    private $versionChangeApplyConfigCommandHandler;

    /**
     * @var CacheClearerInterface
     */
    private $cacheClearer;

    /** @var bool */
    private $cleared = false;

    public function __construct(
        LoggerInterface $logger,
        Repository $moduleRepository,
        TabCollectionProviderInterface $tabCollectionProvider,
        ContextBuilder $contextBuilder,
        Client $distributionClient,
        AdminAuthenticationProvider $adminAuthenticationProvider,
        VersionChangeApplyConfigCommandHandler $versionChangeApplyConfigCommandHandler,
        CacheClearerInterface $cacheClearer
    ) {
        $this->logger = $logger;
        $this->moduleRepository = $moduleRepository;
        $this->tabCollectionProvider = $tabCollectionProvider;
        $this->contextBuilder = $contextBuilder;
        $this->distributionClient = $distributionClient;
        $this->adminAuthenticationProvider = $adminAuthenticationProvider;
        $this->versionChangeApplyConfigCommandHandler = $versionChangeApplyConfigCommandHandler;
        $this->cacheClearer = $cacheClearer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ModuleManagementEvent::INSTALL => [
                ['clearCatalogCache'],
                ['onInstall'],
            ],
            ModuleManagementEvent::POST_INSTALL => [
                ['clearCatalogCache'],
                ['onPostInstall'],
            ],
            ModuleManagementEvent::UNINSTALL => [
                ['clearCatalogCache'],
                ['onUninstall'],
            ],
            ModuleManagementEvent::ENABLE => [
                ['clearSfCache'],
                ['clearCatalogCache'],
                ['onEnable'],
            ],
            ModuleManagementEvent::DISABLE => [
                ['clearSfCache'],
                ['clearCatalogCache'],
                ['onDisable'],
            ],
            ModuleManagementEvent::ENABLE_MOBILE => [
                ['onEnableOnMobile'],
            ],
            ModuleManagementEvent::DISABLE_MOBILE => [
                ['onDisableOnMobile'],
            ],
            ModuleManagementEvent::UPGRADE => [
                ['clearCatalogCache'],
                ['onUpgrade'],
            ],
            ModuleManagementEvent::RESET => [
                ['clearSfCache'],
                ['clearCatalogCache'],
                ['onReset'],
            ],
        ];
    }

    public function clearCatalogCache(): void
    {
        $this->moduleRepository->clearCache();
        $this->tabCollectionProvider->clearCache();
        $this->contextBuilder->clearCache();
    }

    public function clearSfCache(ModuleManagementEvent $event): void
    {
        if (!$this->cleared) {
            $this->cacheClearer->clear();
            $this->cleared = true;
        }
    }

    public function onInstall(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::INSTALL, $event);
    }

    public function onPostInstall(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::POST_INSTALL, $event);

        $module = $event->getModule();
        if (defined('PS_INSTALLATION_IN_PROGRESS') && 'ps_mbo' === $module->get('name')) {
            // Update position of hook dashboardZoneTwo
            /** @var \ps_mbo $psMbo */
            $psMbo = $module->getInstance();
            $psMbo->putMboDashboardZoneTwoAtLastPosition();
        }
    }

    public function onUninstall(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::UNINSTALL, $event);
    }

    public function onEnable(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::ENABLE, $event);
    }

    public function onDisable(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::DISABLE, $event);
    }

    public function onEnableOnMobile(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::ENABLE_MOBILE, $event);
    }

    public function onDisableOnMobile(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::DISABLE_MOBILE, $event);
    }

    public function onUpgrade(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::UPGRADE, $event);

        $module = $event->getModule();
        if ('ps_mbo' === $module->get('name')) {
            // Update shop config to transmit correct versions to Distribution API
            /** @var \ps_mbo $psMbo */
            $psMbo = $module->getInstance();
            $psMbo->updateShop();

            // Apply config dur to PS and MBO version changes
            $this->applyConfigOnVersionChange($module);
        }
    }

    public function onReset(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::RESET, $event);
    }

    protected function logEvent(string $eventName, ModuleManagementEvent $event): void
    {
        $data = $this->contextBuilder->getEventContext();
        $data['event_name'] = $eventName;
        $data['module_name'] = $event->getModule()->get('name');

        $this->distributionClient->setBearer($this->adminAuthenticationProvider->getMboJWT());
        $this->distributionClient->trackEvent($data);
    }

    private function applyConfigOnVersionChange(ModuleInterface $module)
    {
        /** @var Module $module */
        $command = new VersionChangeApplyConfigCommand(
            _PS_VERSION_,
            (string) $module->disk->get('version')
        );

        $this->versionChangeApplyConfigCommandHandler->handle($command);
    }
}
