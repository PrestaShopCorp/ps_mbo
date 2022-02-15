<?php

namespace PrestaShop\Module\Mbo\Addons\Subscriber;

use PrestaShopBundle\Event\ModuleManagementEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
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
