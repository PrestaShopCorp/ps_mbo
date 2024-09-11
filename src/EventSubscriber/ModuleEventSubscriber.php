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
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ModuleManagementEvent::UPGRADE => 'pushDistributionTracking',
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
}
