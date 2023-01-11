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
include_once __DIR__ . '/../src/UpgradeTracker.php';

use PrestaShop\Module\Mbo\UpgradeTracker;

/**
 * @param ps_mbo $module
 *
 * @return bool
 */
function upgrade_module_2_0_3($module)
{
    $return = true;

    // Rename tabs
    $tabsToRename = [
        'AdminPsMboModule' => 'Marketplace',
        'AdminModulesCatalog' => 'Marketplace',
        'AdminParentModulesCatalog' => 'Marketplace',
        'AdminAddonsCatalog' => 'Spotlighted Modules',
    ];
    foreach ($tabsToRename as $className => $name) {
        $tabNameByLangId = [];
        foreach (Language::getIDs(false) as $langId) {
            $language = new Language($langId);
            $tabNameByLangId[$langId] = $name;
        }

        $tabId = Tab::getIdFromClassName($className);

        if ($tabId !== false) {
            $tab = new Tab($tabId);
            $tab->name = $tabNameByLangId;
            $tab->wording = $name;
            $tab->wording_domain = 'Admin.Navigation.Menu';
            $return &= $tab->save();
        }
    }

    // Change tabs positions
    $return &= $module->changeTabPosition('AdminParentModulesCatalog', 0);
    $return &= $module->changeTabPosition('AdminModulesSf', 1);

    (new UpgradeTracker())->postTracking($module, $module->version);

    return $return;
}
