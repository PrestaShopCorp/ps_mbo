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

    /**
     * @var ModuleRepository
     */
    private $coreModuleRepository;

    public function __construct(
        LoggerInterface $logger,
        Repository $moduleRepository,
        TabCollectionProviderInterface $tabCollectionProvider,
        ContextBuilder $contextBuilder,
        Client $distributionClient,
        AdminAuthenticationProvider $adminAuthenticationProvider,
        VersionChangeApplyConfigCommandHandler $versionChangeApplyConfigCommandHandler
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
                ['clearCatalogCache'],
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
            ModuleManagementEvent::ENABLE_MOBILE => [
                ['clearCatalogCache'],
                ['onEnableMobile'],
            ],
            ModuleManagementEvent::DISABLE_MOBILE => [
                ['clearCatalogCache'],
                ['onDisableMobile'],
            ],
            ModuleManagementEvent::UPGRADE => [
                ['clearCatalogCache'],
                ['onUpgrade'],
            ],
            ModuleManagementEvent::RESET => [
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

    public function onEnableMobile(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::ENABLE_MOBILE, $event);
    }

    public function onDisableMobile(ModuleManagementEvent $event): void
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
        $command = new VersionChangeApplyConfigCommand(
            _PS_VERSION_,
            $module->disk->get('version')
        );

        $configCollection = $this->versionChangeApplyConfigCommandHandler->handle($command);
    }
}
