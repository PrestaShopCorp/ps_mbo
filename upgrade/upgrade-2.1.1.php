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
function upgrade_module_2_1_1($module)
{
    $return = true;

    $module->updateHooks();

    if (true === (bool) version_compare(_PS_VERSION_, '1.7.8', '>=')) {
        $tabsToRename = [
            'AdminPsMboAddons' => [
                'new_name' => 'Module selection',
                'trans_domain' => 'Admin.Navigation.Menu',
            ],
            'AdminAddonsCatalog' => [
                'new_name' => 'Module selection',
                'trans_domain' => 'Admin.Navigation.Menu',
            ],
        ];

        foreach ($tabsToRename as $className => $names) {
            $tabId = Tab::getIdFromClassName($className);

            if ($tabId !== false) {
                $tabNameByLangId = [];
                $transDomain = isset($names['trans_domain']) ? $names['trans_domain'] : 'Modules.Mbo.Global';
                foreach (Language::getIDs(false) as $langId) {
                    $language = new Language($langId);
                    $tabNameByLangId[$langId] = (string)$module->getTranslator()->trans($names['new_name'], [], $transDomain, $language->getLocale());
                }

                $tab = new Tab($tabId);
                $tab->name = $tabNameByLangId;
                $tab->wording = $names['new_name'];
                $tab->wording_domain = $transDomain;
                $return &= $tab->save();
            }
        }
    }
    (new UpgradeTracker())->postTracking($module, $module->version);

    return $return;
}
