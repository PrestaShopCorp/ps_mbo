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
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ModuleManagementEvent::INSTALL => [
                ['onInstall'],
            ],
            ModuleManagementEvent::POST_INSTALL => [
                ['onPostInstall'],
            ],
            ModuleManagementEvent::UNINSTALL => [
                ['onUninstall'],
            ],
            ModuleManagementEvent::ENABLE => [
                ['onEnable'],
            ],
            ModuleManagementEvent::DISABLE => [
                ['onDisable'],
            ],
            ModuleManagementEvent::UPGRADE => [
                ['onUpgrade'],
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

    public function onUpgrade(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::UPGRADE);
    }

    public function onReset(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::RESET);
    }

    private function logEvent(string $eventName)
    {
        $this->logger->info(sprintf('Event %s triggered', $eventName));
    }
}
