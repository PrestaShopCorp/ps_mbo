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
use Exception;
use LanguageCore as Language;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
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
        'AdminPsMboModule' => [
            'name' => 'Marketplace',
            'visible' => true,
            'position' => 0,
            'class_name' => 'AdminPsMboModule',
            'parent_class_name' => 'AdminPsMboModuleParent',
        ],
        'AdminPsMboSelection' => [
            'name' => 'Modules in the spotlight',
            'visible' => false,
            'position' => 1,
            'class_name' => 'AdminPsMboSelection',
            'parent_class_name' => 'AdminPsMboModuleParent',
            'wording' => 'Modules in the spotlight',
            'wording_domain' => 'Modules.Mbo.Modulesselection',
        ],
        'AdminPsMboRecommended' => [
            'name' => 'Modules recommandés',
            'visible' => true,
            'class_name' => 'AdminPsMboRecommended',
        ],
        'AdminPsMboTheme' => [
            'name' => 'Themes Catalog',
            'visible' => true,
            'position' => 2,
            'class_name' => 'AdminPsMboTheme',
            'parent_class_name' => 'AdminParentThemes',
            'wording' => 'Themes Catalog',
            'wording_domain' => 'Modules.Mbo.Themescatalog',
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
     * @throws Exception
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
    public function installTab(array $tabData): bool
    {
        $tabNameByLangId = array_fill_keys(
            Language::getIDs(false),
            $tabData['name']
        );

        $idParent = empty($tabData['parent_class_name']) ? -1 : Tab::getIdFromClassName($tabData['parent_class_name']);

        $tab = new Tab();

        // This will reorder the tabs starting with 1
        $tab->cleanPositions($idParent);

        $tab->module = $this->name;
        $tab->class_name = $tabData['class_name'];
        // For the position, we put the tab at the end of the parent children
        // and after that, place it in the right position
        $tab->position = Tab::getNewLastPosition($idParent);
        $tab->id_parent = $idParent;
        $tab->name = $tabNameByLangId;
        $tab->active = $tabData['visible'] ?: false;

        if (false === self::checkModuleStatus()) {
            // If the MBO module is not active, we disable all the tabs. They will be enabled when MBO is enabling
            $tab->active = false;
        }

        if (!empty($tabData['wording']) && !empty($tabData['wording_domain'])) {
            $tab->wording = $tabData['wording'];
            $tab->wording_domain = $tabData['wording_domain'];
        }

        if (false === $tab->add()) {
            return false;
        }

        if (Validate::isLoadedObject($tab)) {
            $position = $tabData['position'] ?? 0;
            $this->putTabInPosition($tab, $position);
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
     * @throws \PrestaShopException
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
        
        // First disable all the tabs to reset it all
        foreach ($tabData as $tabInDb) {
            try {
                $tab = new Tab((int) $tabInDb);
            } catch (\PrestaShopDatabaseException|\PrestaShopException $e) {
                continue;
            }

            if (false === Validate::isLoadedObject($tab)) {
                continue;
            }

            $tab->active = false;
            $tab->save();
        }

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
            $this->installTab(static::$ADMIN_CONTROLLERS[$newTab]);
        }

        foreach ($currentModuleTabs as $currentModuleTab) {
            if (!in_array($currentModuleTab, $oldTabs) && !in_array($currentModuleTab, $newTabs)) {
                $tabData = static::$ADMIN_CONTROLLERS[$currentModuleTab];

                $idParent = empty($tabData['parent_class_name'])
                    ? -1
                    : Tab::getIdFromClassName($tabData['parent_class_name'])
                ;

                $tabId = Tab::getIdFromClassName($tabData['class_name']);
                try {
                    $tab = new Tab($tabId);

                    // This will reorder the tabs starting with 1
                    $tab->cleanPositions($idParent);
                } catch (\PrestaShopDatabaseException|\PrestaShopException $e) {
                    ErrorHelper::reportError($e);
                    throw new Exception('Failed to clean parent tab positions', 0, $e);
                }
            }
        }

        foreach ($currentModuleTabs as $currentModuleTab) {
            if (!in_array($currentModuleTab, $oldTabs) && !in_array($currentModuleTab, $newTabs)) {
                $this->updateTab(static::$ADMIN_CONTROLLERS[$currentModuleTab]);
            }
        }
    }

    /**
     * Updates an already existing tab.
     *
     * @param array $tabData
     */
    private function updateTab(array $tabData): bool
    {
        $tabId = Tab::getIdFromClassName($tabData['class_name']);
        try {
            $tab = new Tab($tabId);
        } catch (\PrestaShopDatabaseException|\PrestaShopException $e) {
            return false;
        }

        if (false === Validate::isLoadedObject($tab)) {
            return false;
        }

        $tabNameByLangId = array_fill_keys(
            Language::getIDs(false),
            $tabData['name']
        );

        $idParent = empty($tabData['parent_class_name']) ? -1 : Tab::getIdFromClassName($tabData['parent_class_name']);

        $tab->id_parent = $idParent;
        $tab->name = $tabNameByLangId;
        $tab->active = $tabData['visible'] ?: false;

        if (false === self::checkModuleStatus()) {
            // If the MBO module is not active, we disable all the tabs. They will be enabled when MBO is enabling
            $tab->active = false;
        }

        if (!empty($tabData['wording']) && !empty($tabData['wording_domain'])) {
            $tab->wording = $tabData['wording'];
            $tab->wording_domain = $tabData['wording_domain'];
        }

        if (false === $tab->save()) {
            return false;
        }

        try {
            $tab = new Tab($tabId);
        } catch (\PrestaShopDatabaseException|\PrestaShopException $e) {
            return false;
        }

        if (
            Validate::isLoadedObject($tab)
            && isset($tabData['position'])
            && (int) $tab->position !== (int) $tabData['position']
        ) {
            $this->putTabInPosition($tab, $tabData['position']);
        }

        return true;
    }

    private function putTabInPosition(Tab $tab, int $position): void
    {
        // Check tab position in DB
        $dbTabPosition = Db::getInstance()->getValue('
			SELECT `position`
			FROM `' . _DB_PREFIX_ . 'tab`
			WHERE `id_tab` = ' . (int) $tab->id
        );

        if ((int) $dbTabPosition === (int) $position) {
            // Nothing to do, tab is already in the right position
            return;
        }

        Db::getInstance()->execute(
            '
            UPDATE `' . _DB_PREFIX_ . 'tab`
            SET `position` = `position`+1
            WHERE `id_parent` = ' . (int) $tab->id_parent . '
            AND `position` >= ' . $position . '
            AND `id_tab` <> ' . (int) $tab->id
        );

        Db::getInstance()->execute(
            '
                UPDATE `' . _DB_PREFIX_ . 'tab`
                SET `position` = ' . $position . '
                WHERE `id_tab` = ' . (int) $tab->id
        );
    }
}
