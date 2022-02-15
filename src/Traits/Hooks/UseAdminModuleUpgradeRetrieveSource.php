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

namespace PrestaShop\Module\Mbo\Traits\Hooks;

use PrestaShop\Module\Mbo\Exception\ModuleUpgradeNotNeededException;
use PrestaShop\Module\Mbo\Modules\Module;

trait UseAdminModuleUpgradeRetrieveSource
{
    /**
     * Hook actionAdminModuleUpgradeRetrieveSource.
     */
    public function hookActionAdminModuleUpgradeRetrieveSource(array $params): ?string
    {
        if (empty($params['name']) || empty($params['current_version'])) {
            return null;
        }

        $moduleName = (string) $params['name'];
        $moduleVersion = (string) $params['current_version'];

        $moduleRepository = $this->get('mbo.modules.repository');
        // We need to clear cache to get fresh data from addons
        $moduleRepository->clearCache();
        /** @var Module $module */
        $module = $moduleRepository->getModule($moduleName);

        if (null === $module) {
            return null;
        }

        // If the current installed version is greater or equal than the one returned by Addons, do nothing
        if (version_compare($moduleVersion, (string) $module->get('version_available'), 'ge')) {
            throw new ModuleUpgradeNotNeededException();
        }

        return $this->get('mbo.addon.module.data_provider.addons')->downloadModule(
            (int) $module->get('id')
        );
    }
}
