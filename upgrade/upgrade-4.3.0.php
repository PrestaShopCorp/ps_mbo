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
function upgrade_module_4_3_0(Module $module): bool
{
    $module->updateHooks();
    $module->updateTabs();

    // Put the marketplace tab at first position of his parent
    $tab = Tab::getInstanceFromClassName('AdminPsMboModuleParent');

    if (Validate::isLoadedObject($tab)) {
        $tab->position = 0;
        if (false === $tab->save()) {
            return false;
        }

        // This will reorder the tabs starting with 1
        $tab->cleanPositions($tab->id_parent);
    }

    // Rename "Module Selections" to "Spotlighted modules"
    $tab = Tab::getInstanceFromClassName('AdminPsMboSelection');

    if (Validate::isLoadedObject($tab)) {
        $tabNameByLangId = array_fill_keys(
            Language::getIDs(false),
            'Modules in the spotlight'
        );
        $tab->name = $tabNameByLangId;
        $tab->wording = 'Modules in the spotlight';
        $tab->wording_domain = 'Modules.Mbo.Modulesselection';

        if (false === $tab->save()) {
            return false;
        }

        $module->postponeTabsTranslations();
    }

    return true;
}
