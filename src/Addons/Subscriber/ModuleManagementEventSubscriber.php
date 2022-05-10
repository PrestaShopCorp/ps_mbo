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

use PrestaShop\Module\Mbo\Module\Repository;
use PrestaShop\Module\Mbo\Tab\TabCollectionProviderInterface;
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

    public function __construct(
        LoggerInterface $logger,
        Repository $moduleRepository,
        TabCollectionProviderInterface $tabCollectionProvider
    ) {
        $this->logger = $logger;
        $this->moduleRepository = $moduleRepository;
        $this->tabCollectionProvider = $tabCollectionProvider;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ModuleManagementEvent::INSTALL => [
                ['onInstall'],
                ['clearCatalogCache'],
            ],
            ModuleManagementEvent::POST_INSTALL => [
                ['onPostInstall'],
                ['clearCatalogCache'],
            ],
            ModuleManagementEvent::UNINSTALL => [
                ['onUninstall'],
                ['clearCatalogCache'],
            ],
            ModuleManagementEvent::ENABLE => [
                ['onEnable'],
                ['clearCatalogCache'],
            ],
            ModuleManagementEvent::DISABLE => [
                ['onDisable'],
                ['clearCatalogCache'],
            ],
            ModuleManagementEvent::ENABLE_MOBILE => [
                ['onEnableMobile'],
                ['clearCatalogCache'],
            ],
            ModuleManagementEvent::DISABLE_MOBILE => [
                ['onDisableMobile'],
                ['clearCatalogCache'],
            ],
            ModuleManagementEvent::UPGRADE => [
                ['onUpgrade'],
                ['clearCatalogCache'],
            ],
            ModuleManagementEvent::RESET => [
                ['onReset'],
            ],
        ];
    }

    public function onInstall(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::INSTALL);
    }

    public function clearCatalogCache(): void
    {
        $this->moduleRepository->clearCache();
        $this->tabCollectionProvider->clearCache();
    }

    public function onPostInstall(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::POST_INSTALL);
    }

    public function onUninstall(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::UNINSTALL);
    }

    public function onEnable(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::ENABLE);
    }

    public function onDisable(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::DISABLE);
    }

    public function onEnableMobile(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::ENABLE);
    }

    public function onDisableMobile(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::DISABLE);
    }

    public function onUpgrade(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::UPGRADE);
    }

    public function onReset(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::RESET);
    }

    protected function logEvent(string $eventName): void
    {
        $this->logger->info(sprintf('Event %s triggered', $eventName));
    }
}
