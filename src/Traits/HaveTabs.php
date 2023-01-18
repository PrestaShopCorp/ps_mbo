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
use PrestaShop\Module\Mbo\Distribution\Config\Command\VersionChangeApplyConfigCommand;
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
            'name' => 'Marketplace',
            'visible' => true,
            'position' => 0,
            'class_name' => 'AdminPsMboModuleParent',
            'parent_class_name' => 'AdminParentModulesSf',
        ],
        'AdminPsMboSelection' => [
            'name' => 'Modules in the spotlight',
            'visible' => true,
            'class_name' => 'AdminPsMboSelection',
            'parent_class_name' => 'AdminPsMboModuleParent',
            'wording' => 'Modules in the spotlight',
            'wording_domain' => 'Modules.Mbo.Modulesselection',
        ],
        'AdminPsMboModule' => [
            'name' => 'Marketplace',
            'visible' => true,
            'class_name' => 'AdminPsMboModule',
            'parent_class_name' => 'AdminPsMboModuleParent',
        ],
        'AdminPsMboRecommended' => [
            'name' => 'Modules recommandés',
            'visible' => true,
            'class_name' => 'AdminPsMboRecommended',
        ],
        'AdminPsMboTheme' => [
            'name' => 'Catalogue de thèmes',
            'visible' => true,
            'position' => 1,
            'class_name' => 'AdminPsMboTheme',
            'parent_class_name' => 'AdminParentThemes',
        ],
        'ApiPsMbo' => [
            'name' => 'MBO Api',
            'visible' => false,
            'class_name' => 'ApiPsMbo',
            'parent_class_name' => 'AdminParentModulesSf',
        ],
        'ApiSecurityPsMbo' => [
            'name' => 'MBO Api Security',
            'visible' => false,
            'class_name' => 'ApiSecurityPsMbo',
            'parent_class_name' => 'AdminParentModulesSf',
        ],
    ];

    /**
     * This method is called when module is enabled/disabled.
     *
     * Apply given method on all Tabs
     * Values can be 'install' or 'uninstall'
     * If action is install, the tabs are activated if relevant.
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
     * Used when module is enabled and in upgrade script.
     *
     * @param array $tabData
     *
     * @return bool
     */
    public function installTab(array $tabData, bool $activate = true): bool
    {
        $position = $tabData['position'] ?? 0;
        $tabNameByLangId = array_fill_keys(
            Language::getIDs(false),
            $tabData['name']
        );

        $idParent = empty($tabData['parent_class_name']) ? -1 : $tabId = Tab::getIdFromClassName($tabData['parent_class_name']);

        $tab = new Tab();
        $tab->module = $this->name;
        $tab->class_name = $tabData['class_name'];
        $tab->position = $position;
        $tab->id_parent = $idParent;
        $tab->name = $tabNameByLangId;
        $tab->active = $tabData['visible'] ? $tabData['visible'] : false;

        if (false === $activate) { // This case will happen when upgrading the module. We disable all the tabs
            $tab->active = false;
        }

        if (!empty($tabData['wording']) && !empty($tabData['wording_domain'])) {
            $tab->wording = $tabData['wording'];
            $tab->wording_domain = $tabData['wording_domain'];
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
     * This method is called on module upgrade.
     * Tabs will be updated if the module is active.
     * But they will be all unactivated.
     *
     * Update tabs in DB.
     * Search current tabs registered in DB and compare them with the tabs declared in the module.
     * If a tab is missing, it will be added. If a tab is not declared in the module, it will be removed.
     *
     * @return void
     */
    public function updateTabs(): void
    {
        if (false === self::checkModuleStatus()) {
            // If the MBO module is not active.
            // We don't update the tabs, it will be done when the module is enabled.
            return;
        }

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

        // Delete the tabs that are not relevant anymore
        foreach ($oldTabs as $oldTab) {
            $this->uninstallTab(['class_name' => $oldTab]);
        }
        // Install the new tabs
        foreach ($newTabs as $newTab) {
            $this->installTab(static::$ADMIN_CONTROLLERS[$newTab], false);
        }
    }
}
