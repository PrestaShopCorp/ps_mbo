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

/**
 * @param ps_mbo $module
 *
 * @return bool
 */
function upgrade_module_4_1_1(Module $module): bool
{
    $parentTab = Tab::getInstanceFromClassName('AdminPsMboModuleParent');
    $moduleTab = Tab::getInstanceFromClassName('AdminPsMboModule');
    $languages = Language::getIDs(false);
    $tabNameByLangId = array_fill_keys($languages, 'Marketplace');

    if (Validate::isLoadedObject($parentTab)) {
        $parentTab->name = $tabNameByLangId;
        $parentTab->save();
    }
    if (Validate::isLoadedObject($moduleTab)) {
        $moduleTab->name = $tabNameByLangId;
        $moduleTab->save();
    }

    return true;
}
