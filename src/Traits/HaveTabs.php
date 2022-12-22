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

namespace PrestaShop\Module\Mbo\Traits;

use Db;
use LanguageCore as Language;
use Symfony\Component\String\UnicodeString;
use TabCore as Tab;
use ValidateCore as Validate;

trait HaveTabs
{
    /**
     * @var array[]
     */
    public static $ADMIN_CONTROLLERS = [
        'AdminPsMboModuleParent' => [
            'name' => 'Modules catalog',
            'wording' => 'Modules catalog',
            'wording_domain' => 'Modules.Mbo.Modulescatalog',
            'visible' => true,
            'position' => 1,
            'class_name' => 'AdminPsMboModuleParent',
            'parent_class_name' => 'AdminParentModulesSf',
        ],
        'AdminPsMboSelection' => [
            'name' => 'Module selection',
            'wording' => 'Module selection',
            'wording_domain' => 'Modules.Mbo.Modulesselection',
            'visible' => true,
            'class_name' => 'AdminPsMboSelection',
            'parent_class_name' => 'AdminPsMboModuleParent',
        ],
        'AdminPsMboModule' => [
            'name' => 'Modules catalog',
            'wording' => 'Modules catalog',
            'wording_domain' => 'Modules.Mbo.Modulescatalog',
            'visible' => true,
            'class_name' => 'AdminPsMboModule',
            'parent_class_name' => 'AdminPsMboModuleParent',
        ],
        'AdminPsMboRecommended' => [
            'name' => 'Recommended Modules and Services',
            'wording' => 'Recommended Modules and Services',
            'wording_domain' => 'Modules.Mbo.Recommendedmodulesandservices',
            'visible' => true,
            'class_name' => 'AdminPsMboRecommended',
        ],
        'AdminPsMboTheme' => [
            'name' => 'Themes catalog',
            'wording' => 'Themes catalog',
            'wording_domain' => 'Modules.Mbo.Themescatalog',
            'visible' => true,
            'position' => 1,
            'class_name' => 'AdminPsMboTheme',
            'parent_class_name' => 'AdminParentThemes',
        ],
        'ApiPsMbo' => [
            'name' => 'MBO Api',
            'wording' => 'MBO Api',
            'wording_domain' => 'Modules.Mbo.Global',
            'visible' => false,
            'position' => 1,
            'class_name' => 'ApiPsMbo',
            'parent_class_name' => 'AdminParentModulesSf',
        ],
        'ApiSecurityPsMbo' => [
            'name' => 'MBO Api Security',
            'wording' => 'MBO Api Security',
            'wording_domain' => 'Modules.Mbo.Global',
            'visible' => false,
            'position' => 1,
            'class_name' => 'ApiSecurityPsMbo',
            'parent_class_name' => 'AdminParentModulesSf',
        ],
    ];

    /**
     * Apply given method on all Tabs
     * Values can be 'install' or 'uninstall'
     *
     * @param string $action
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function handleTabAction(string $action): bool
    {
        $methodName = (new UnicodeString($action))->camel() . 'Tab';
        if (!method_exists($this, $methodName)) {
            return false;
        }

        foreach (static::$ADMIN_CONTROLLERS as $tabData) {
            if (false === $this->{$methodName}($tabData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Install Tab.
     * Used in upgrade script.
     *
     * @param array $tabData
     *
     * @return bool
     */
    public function installTab(array $tabData): bool
    {
        $position = $tabData['position'] ?? 0;

        $idParent = empty($tabData['parent_class_name']) ? -1 : $tabId = Tab::getIdFromClassName($tabData['parent_class_name']);

        $tab = new Tab();
        $tab->module = $this->name;
        $tab->class_name = $tabData['class_name'];
        $tab->position = $position;
        $tab->id_parent = $idParent;
        $tab->wording = $tabData['wording'];
        $tab->wording_domain = $tabData['wording_domain'];
        $tab->active = $tabData['visible'];

        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $this->translators[$lang['id_lang']]->trans($tabData['wording'], [], $tabData['wording_domain'], $lang['locale']);
        }

        // This will reorder the tabs starting with 1
        $tab->cleanPositions($idParent);

        if (false === $tab->add()) {
            return false;
        }

        if (Validate::isLoadedObject($tab)) {
            // Updating the id_parent will override the position, that's why we save 2 times
            $tab->position = $position;
            $tab->save();
        }

        return true;
    }

    /**
     * Uninstall Tab.
     * Can be used in upgrade script.
     *
     * @param array $tabData
     *
     * @return bool
     *
     * @throws \PrestaShopException
     */
    public function uninstallTab(array $tabData): bool
    {
        $tabId = Tab::getIdFromClassName($tabData['class_name']);
        $tab = new Tab($tabId);

        if (false === Validate::isLoadedObject($tab)) {
            return false;
        }

        if (false === $tab->delete()) {
            return false;
        }

        return true;
    }

    /**
     * Update tabs in DB.
     * Search current tabs registered in DB and compare them with the tabs declared in the module.
     * If a tab is missing, it will be added. If a tab is not declared in the module, it will be removed.
     *
     * @return void
     */
    public function updateTabs(): void
    {
        $tabData = Db::getInstance()->executeS('
            SELECT class_name
            FROM `' . _DB_PREFIX_ . 'tab`
            WHERE `module` = "' . pSQL($this->name) . '"'
        );

        //Flatten $tabData array
        $tabData = array_unique(array_map('current', $tabData));
        $currentModuleTabs = array_keys(static::$ADMIN_CONTROLLERS);

        $oldTabs = [];
        $newTabs = [];

        // Iterate on DB tabs to get only the old ones
        foreach ($tabData as $tabInDb) {
            if (!in_array($tabInDb, $currentModuleTabs)) {
                $oldTabs[] = $tabInDb;
            }
        }

        // Iterate on module tabs to get only the new ones
        foreach ($currentModuleTabs as $tab) {
            if (!in_array($tab, $tabData)) {
                $newTabs[] = $tab;
            }
        }

        foreach ($oldTabs as $oldTab) {
            $this->uninstallTab(['class_name' => $oldTab]);
        }
        foreach ($newTabs as $newTab) {
            $this->installTab(static::$ADMIN_CONTROLLERS[$newTab]);
        }
    }
}
