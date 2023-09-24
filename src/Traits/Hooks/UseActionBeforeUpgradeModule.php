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

use PrestaShop\Module\Mbo\Module\ActionsManager;
use PrestaShop\PrestaShop\Adapter\Module\ModuleDataProvider;

trait UseActionBeforeUpgradeModule
{
    /**
     * Hook actionBeforeUpgradeModule.
     *
     * @param array $params
     *
     * @throws \PrestaShop\Module\Mbo\Module\Exception\ModuleNewVersionNotFoundException
     * @throws \PrestaShop\Module\Mbo\Module\Exception\UnexpectedModuleSourceContentException
     */
    public function hookActionBeforeUpgradeModule(array $params): void
    {
        // @TODO : Remove this Hook... and don't forget to add migration to unregister it
        return;
        if (!$this->needToDownloadModuleZip($params)) {
            return;
        }
        /** @var ModuleDataProvider $moduleDataProvider */
        $moduleDataProvider = $this->get('prestashop.adapter.data_provider.module');

        if (empty($params['moduleName']) || !$moduleDataProvider->isOnDisk($params['moduleName'])) {
            return;
        }

        $moduleName = (string) $params['moduleName'];

        /** @var ActionsManager $moduleActionsManager */
        $moduleActionsManager = $this->get('mbo.modules.actions_manager');

        $module = $moduleActionsManager->findVersionForUpdate($moduleName);
        if (null !== $module) {
            $moduleActionsManager->downloadAndReplaceModuleFiles($module);
        }
    }

    /**
     * We proceed the download only if the update is launched by a back office action.
     *
     * @param array $params
     *
     * @return bool
     */
    private function needToDownloadModuleZip(array $params): bool
    {
        if (!empty($params['route']) && $params['route'] === 'admin_module_manage_action') {
            return true;
        }
        if (!empty($params['request']) && $params['request']->get('_controller') === 'PrestaShopBundle\Controller\Admin\Improve\ModuleController::moduleAction' && $params['request']->get('action') === 'upgrade') {
            return true;
        }

        return false;
    }
}
