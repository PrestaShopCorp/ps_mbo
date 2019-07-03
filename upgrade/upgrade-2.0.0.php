<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @param ps_mbo $module
 *
 * @return bool
 */
function upgrade_module_2_0_0($module)
{
    $result = true;

    // Retrieve all hooks registered with MBO
    $hookData = Db::getInstance()->executeS('
        SELECT DISTINCT(`id_hook`)
        FROM `' . _DB_PREFIX_ . 'hook_module`
        WHERE `id_module` = ' . (int) $module->id
    );

    // Some hooks are no longer used, we unregister them.
    if (!empty($hookData)) {
        foreach ($hookData as $row) {
            $result &= $module->unregisterHook((int) $row['id_hook']);
            $result &= $module->unregisterExceptions((int) $row['id_hook']);
        }
    }

    // Some hooks are added, we register them.
    foreach ($module->hooks as $hook) {
        if (!$module->isRegisteredInHook($hook)) {
            $result &= $module->registerHook($hook);
        }
    }

    // We migrate Module Selections Tab to MBO
    if (isset($module->adminTabs['AdminPsMboAddons'])) {
        $result &= $module->installTab($module->adminTabs['AdminPsMboAddons']);
    }

    // We create Module Recommended Tab to MBO
    if (isset($module->adminTabs['AdminPsMboRecommended'])) {
        $result &= $module->installTab($module->adminTabs['AdminPsMboRecommended']);
    }

    // We create Week Advice Tab to MBO
    if (isset($module->adminTabs['AdminPsMboWeekAdvice'])) {
        $result &= $module->installTab($module->adminTabs['AdminPsMboWeekAdvice']);
    }

    // Uninstall psaddonsconnect since it was now part of MBO
    $result &= $module->uninstallPsAddonsConnect();

    // Mbo dashboard widget should be at first position
    $result &= $module->setDashboardWidgetPosition();

    return $result;
}
