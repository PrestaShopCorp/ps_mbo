<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @param ps_mbo $module
 *
 * @return bool
 */
function upgrade_module_2_0_0($module)
{
    // Retrieve all hooks registered with MBO
    $hookData = Db::getInstance()->executeS('
        SELECT DISTINCT(`id_hook`)
        FROM `' . _DB_PREFIX_ . 'hook_module`
        WHERE `id_module` = ' . (int) $module->id
    );

    // Some hooks are no longer used, we unregister them.
    if (!empty($hookData)) {
        foreach ($hookData as $row) {
            if (false === $module->unregisterHook((int) $row['id_hook'])) {
                return false;
            }

            if (false === $module->unregisterExceptions((int) $row['id_hook'])) {
                return false;
            }
        }
    }

    // Some hooks are added, we register them.
    if (false === $module->registerHook(ps_mbo::HOOKS)) {
        return false;
    }

    // We migrate Module Selections Tab to MBO
    if (false === $module->installTab(ps_mbo::ADMIN_CONTROLLERS['AdminPsMboAddons'])) {
        return false;
    }

    // We create Module Recommended Tab to MBO
    if (false === $module->installTab(ps_mbo::ADMIN_CONTROLLERS['AdminPsMboRecommended'])) {
        return false;
    }

    return true;
}
