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

    $module->updateHooks();

    // Rename tabs
    $tabsToRename = [
        'AdminPsMboModule' => [
            'new_name' => 'Marketplace',
        ],
        'AdminModulesCatalog' => [
            'new_name' => 'Marketplace',
        ],
        'AdminParentModulesCatalog' => [
            'new_name' => 'Marketplace',
        ],
    ];

    if (true === (bool) version_compare(_PS_VERSION_, '1.7.8', '>=')) {
        $tabsToRename += [
            'AdminPsMboAddons' => [
                'new_name' => 'Modules in the spotlight',
                'trans_domain' => 'Modules.Mbo.Modulesselection',
            ],
            'AdminAddonsCatalog' => [
                'new_name' => 'Modules in the spotlight',
                'trans_domain' => 'Modules.Mbo.Modulesselection',
            ],
        ];
    }
    foreach ($tabsToRename as $className => $names) {
        $tabNameByLangId = [];
        foreach (Language::getIDs(false) as $langId) {
            $language = new Language($langId);
            $tabNameByLangId[$langId] = (string) $module->getTranslator()->trans($names['new_name'], [], isset($names['trans_domain']) ? $names['trans_domain'] : 'Modules.Mbo.Global', $language->getLocale());
        }

        $tabId = Tab::getIdFromClassName($className);

        if ($tabId !== false) {
            $tab = new Tab($tabId);
            $tab->name = $tabNameByLangId;
            if (true === (bool) version_compare(_PS_VERSION_, '1.7.8', '>=')) {
                $tab->wording = $names['new_name'];
                $tab->wording_domain = isset($names['trans_domain']) ? $names['trans_domain'] : 'Modules.Mbo.Global';
            }
            $return &= $tab->save();
        }
    }

    // Change tabs positions
    $return &= $module->changeTabPosition('AdminParentModulesCatalog', 0);
    $return &= $module->changeTabPosition('AdminModulesSf', 1);

    $module->postponeTabsTranslations();

    (new UpgradeTracker())->postTracking($module, $module->version);

    return $return;
}
