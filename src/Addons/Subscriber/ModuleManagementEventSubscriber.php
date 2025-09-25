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
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;
use PrestaShopBundle\Event\ModuleManagementEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Performs actions on Module lifecycle events
 */
class ModuleManagementEventSubscriber implements EventSubscriberInterface
{
    const WATERMARK_FILENAME = '/.info';

    public function __construct(
        private readonly Repository $moduleRepository,
        private readonly ContextBuilder $contextBuilder,
        private readonly Client $distributionClient,
        private readonly AdminAuthenticationProvider $adminAuthenticationProvider,
        private readonly VersionChangeApplyConfigCommandHandler $versionChangeApplyConfigCommandHandler,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ModuleManagementEvent::INSTALL => [
                ['clearCatalogCache'],
                ['onInstall'],
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
        $this->contextBuilder->clearCache();
    }

    public function onInstall(ModuleManagementEvent $event): void
    {
        $this->logEvent(ModuleManagementEvent::INSTALL, $event);
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
        $data['module_version'] = $event->getModule()->get('version');

        if (in_array($eventName, [
            ModuleManagementEvent::INSTALL,
            ModuleManagementEvent::UPGRADE,
        ])) {
            $data['module_watermark'] = $this->getModuleWatermark($event->getModule());
        }
        try {
            $this->distributionClient->setBearer($this->adminAuthenticationProvider->getMboJWT());
            $this->distributionClient->trackEvent($data);
        } catch (\Exception $e) {
            // Do nothing, we don't want to block the module action
        }
    }

    private function getModuleWatermark(ModuleInterface $module): string
    {
        $fileName = _PS_MODULE_DIR_ . $module->get('name') . self::WATERMARK_FILENAME;
        if (!file_exists($fileName)) {
            return '';
        }

        try {
            $fileHandle = fopen($fileName, 'r');
            $contents = fread($fileHandle, filesize($fileName));
            fclose($fileHandle);
        } catch (\Exception $e) {
            $contents = '';
        }

        return $contents ?: '';
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
