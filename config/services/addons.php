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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PrestaShop\Module\Mbo\Addons\Subscriber\ModuleManagementEventSubscriber;
use ps_mbo;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    //Only load event subscriber when module is enabled to avoid logging events if disabled
    if (ps_mbo::checkModuleStatus()) {
        $services->set('mbo.addons.event_subscriber', ModuleManagementEventSubscriber::class)
        ->args([
            ref('logger'),
            ref('mbo.modules.repository'),
            ref('mbo.tab.collection.provider'),
            ref('mbo.cdc.context_builder'),
            ref('mbo.cdc.client.distribution_api'),
            ref('mbo.security.admin_authentication.provider'),
            ref('mbo.distribution.api_version_change_config_apply_handler'),
            ref('mbo.symfony_cache_clearer'),
        ])
        ->public()
        ->tag('kernel.event_subscriber');
    }
};
