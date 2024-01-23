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

use PrestaShop\Module\Mbo\Helpers\ErrorHelper;

trait UseActionModuleRegisterHookAfter
{
    /**
     * Hook actionModuleRegisterHookAfter.
     * Triggered after a hook registration, by any module.
     */
    public function hookActionModuleRegisterHookAfter(array $params): void
    {
        try {
            $hookName = (string) $params['hook_name'];

            // The MBO hook 'dashboardZoneTwo' must be at the max position
            if ('DashboardZoneTwo' === mb_ucfirst($hookName)) {
                $this->putMboDashboardZoneTwoAtLastPosition();
            }
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);
        }
    }

    public function putMboDashboardZoneTwoAtLastPosition(): void
    {
        // Check if the hook exists and get it's ID
        $sql = 'SELECT h.`id_hook`
                    FROM `' . _DB_PREFIX_ . 'hook` h
                    WHERE UPPER(h.`name`) = UPPER(\'dashboardZoneTwo\')';
        $row = \Db::getInstance()->getRow($sql);
        if (!$row) {
            return;
        }
        $idHook = $row['id_hook'];

        //Get Module MBO ID
        $sql = 'SELECT m.`id_module`
                    FROM `' . _DB_PREFIX_ . 'module` m
                    WHERE m.`name` = \'ps_mbo\'';
        $row = \Db::getInstance()->getRow($sql);
        $psMboId = $row['id_module'];

        foreach (\Shop::getShops(true, null, true) as $shopId) {
            // Get module position in hook
            $sql = 'SELECT MAX(`position`) AS position
                    FROM `' . _DB_PREFIX_ . 'hook_module`
                    WHERE `id_hook` = ' . (int) $idHook . ' AND `id_shop` = ' . (int) $shopId;
            if (!$position = \Db::getInstance()->getValue($sql)) {
                $position = 0;
            }

            // Check if MBO is not already at last position
            $sql = 'SELECT `position`
                    FROM `' . _DB_PREFIX_ . 'hook_module`
                    WHERE `id_hook` = ' . (int) $idHook . ' AND `id_module` = ' . (int) $psMboId . ' AND `id_shop` = ' . (int) $shopId;
            $mboPosition = \Db::getInstance()->getValue($sql);
            if ($mboPosition === $position) {
                // Nothing to do, MBO is already at last position
                return;
            }

            // Update psMbo position for the hook
            \Db::getInstance()->update(
                'hook_module',
                [
                    'position' => (int) ($position + 1),
                ],
                '`id_module` = ' . (int) $psMboId . ' AND `id_hook` = ' . (int) $idHook . ' AND `id_shop` = ' . (int) $shopId
            );
        }
    }
}
