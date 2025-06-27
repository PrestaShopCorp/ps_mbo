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

namespace PrestaShop\Module\Mbo\EventSubscriber;

use PrestaShop\Module\Mbo\Distribution\AuthenticationProvider;
use PrestaShop\Module\Mbo\Distribution\Client;
use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use PrestaShop\Module\Mbo\UpgradeTracker;
use PrestaShopBundle\Event\ModuleManagementEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listen upgrade events from module manager, and push event to distribution event for tracking purpose.
 */
class ModuleEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AuthenticationProvider
     */
    private $adminAuthenticationProvider;
    /**
     * @var ContextBuilder
     */
    private $contextBuilder;

    /**
     * @var Client
     */
    private $distributionClient;

    const WATERMARK_FILENAME = '/.info';

    /**
     * @param LoggerInterface $logger
     * @param AuthenticationProvider $adminAuthenticationProvider
     * @param ContextBuilder $contextBuilder
     * @param Client $distributionClient
     */
    public function __construct(
        LoggerInterface $logger,
        AuthenticationProvider $adminAuthenticationProvider,
        ContextBuilder $contextBuilder,
        Client $distributionClient
    ) {
        $this->logger = $logger;
        $this->adminAuthenticationProvider = $adminAuthenticationProvider;
        $this->contextBuilder = $contextBuilder;
        $this->distributionClient = $distributionClient;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ModuleManagementEvent::UPGRADE => [
                ['pushDistributionTracking'],
                ['logModuleUpgrade'],
            ],
            ModuleManagementEvent::INSTALL => [
                ['logModuleInstall'],
            ],
        ];
    }

    /**
     * @param ModuleManagementEvent $event
     */
    public function pushDistributionTracking(ModuleManagementEvent $event)
    {
        $module = $event->getModule();
        $moduleName = $module->get('name');
        if ('ps_mbo' !== $moduleName) {
            return;
        }

        (new UpgradeTracker())->postTracking($module->getInstance());
    }

    public function logModuleUpgrade(ModuleManagementEvent $event)
    {
        $this->logEvent(ModuleManagementEvent::UPGRADE, $event);
    }

    public function logModuleInstall(ModuleManagementEvent $event)
    {
        $this->logEvent(ModuleManagementEvent::INSTALL, $event);
    }

    private function logEvent(string $eventName, ModuleManagementEvent $event)
    {
        $data = $this->contextBuilder->getEventContext();
        $data['event_name'] = $eventName;
        $data['module_name'] = $event->getModule()->get('name');
        if (in_array($eventName, [
            ModuleManagementEvent::INSTALL,
            ModuleManagementEvent::UPGRADE,
        ])) {
            $data['module_watermark'] = $this->getModuleWatermark($event->getModule());
        }

        $this->distributionClient->setBearer($this->adminAuthenticationProvider->getMboJWT());
        $this->distributionClient->trackEvent($data);
    }

    private function getModuleWatermark($module)
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
            // If the file is not readable, return an empty string
            return $contents = '';
        }

        return $contents;
    }
}
