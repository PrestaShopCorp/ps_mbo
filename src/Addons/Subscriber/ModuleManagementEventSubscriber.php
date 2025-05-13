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

    private $shutdownClearCacheRegistered = false;

    public function __construct(
        LoggerInterface $logger,
        Repository $moduleRepository,
        TabCollectionProviderInterface $tabCollectionProvider,
        ContextBuilder $contextBuilder,
        Client $distributionClient,
        AdminAuthenticationProvider $adminAuthenticationProvider,
        VersionChangeApplyConfigCommandHandler $versionChangeApplyConfigCommandHandler,
    ) {
        $this->logger = $logger;
        $this->moduleRepository = $moduleRepository;
        $this->tabCollectionProvider = $tabCollectionProvider;
        $this->contextBuilder = $contextBuilder;
        $this->distributionClient = $distributionClient;
        $this->adminAuthenticationProvider = $adminAuthenticationProvider;
        $this->versionChangeApplyConfigCommandHandler = $versionChangeApplyConfigCommandHandler;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ModuleManagementEvent::INSTALL => [
                ['clearCatalogCache'],
                ['onInstall'],
            ],
            ModuleManagementEvent::POST_INSTALL => [
                ['clearCatalogCacheOnShutdown'],
                ['onPostInstall'],
            ],
            ModuleManagementEvent::UNINSTALL => [
                ['clearCatalogCache'],
                ['onUninstall'],
            ],
            ModuleManagementEvent::ENABLE => [
                ['clearCatalogCache'],
                ['onEnable'],
            ],
            ModuleManagementEvent::DISABLE => [
                ['clearCatalogCache'],
                ['onDisable'],
            ],
            ModuleManagementEvent::UPGRADE => [
                ['clearCatalogCache'],
                ['onUpgrade'],
            ],
            ModuleManagementEvent::RESET => [
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

    public function clearCatalogCacheOnShutdown(): void
    {
        if ($this->shutdownClearCacheRegistered) {
            return;
        }

        $this->shutdownClearCacheRegistered = true;
        register_shutdown_function(function () {
            $this->clearCatalogCache();
        });
    }

    public function onInstall(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::INSTALL, $event);
    }

    public function onPostInstall(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::POST_INSTALL, $event);
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

    public function onUpgrade(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::UPGRADE, $event);

        $module = $event->getModule();
        if ('ps_mbo' === $module->get('name')) {
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
        try {
            $data = $this->contextBuilder->getEventContext();
        } catch (\Exception $e) {
            // Do nothing, we don't want to block the module action
            return;
        }
        $data['event_name'] = $eventName;
        $data['module_name'] = $event->getModule()->get('name');

        try {
            $this->distributionClient->setBearer($this->adminAuthenticationProvider->getMboJWT());
            $this->distributionClient->trackEvent($data);
        } catch (\Exception $e) {
            // Do nothing, we don't want to block the module action
        }
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
