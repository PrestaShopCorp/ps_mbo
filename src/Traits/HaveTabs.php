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

use LanguageCore as Language;
use PrestaShopBundle\Entity\Repository\TabRepository;
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
            'name' => 'Catalogue de modules',
            'visible' => true,
            'position' => 1,
            'class_name' => 'AdminPsMboModuleParent',
            'parent_class_name' => 'AdminParentModulesSf',
        ],
        'AdminPsMboSelection' => [
            'name' => 'Sélection de modules',
            'visible' => true,
            'class_name' => 'AdminPsMboSelection',
            'parent_class_name' => 'AdminPsMboModuleParent',
        ],
        'AdminPsMboModule' => [
            'name' => 'Catalogue de modules',
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
            'position' => 1,
            'class_name' => 'ApiPsMbo',
            'parent_class_name' => 'AdminParentModulesSf',
        ],
        'ApiPsMboSecurity' => [
            'name' => 'MBO Api Security',
            'visible' => false,
            'position' => 1,
            'class_name' => 'ApiSecurityPsMbo',
            'parent_class_name' => 'AdminParentModulesSf',
        ],
    ];

    /**
     * @var TabRepository
     */
    protected $tabRepository;

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
        /** @var TabRepository $tabRepository */
        $tabRepository = $this->get('prestashop.core.admin.tab.repository');
        $this->tabRepository = $tabRepository;
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
        $tabNameByLangId = array_fill_keys(
            Language::getIDs(false),
            $tabData['name']
        );

        $idParent = empty($tabData['parent_class_name']) ? -1 : $this->tabRepository->findOneIdByClassName($tabData['parent_class_name']);

        $tab = new Tab();
        $tab->module = $this->name;
        $tab->class_name = $tabData['class_name'];
        $tab->position = $position;
        $tab->id_parent = $idParent;
        $tab->name = $tabNameByLangId;
        $tab->active = $tabData['visible'];

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
        $tabId = $this->tabRepository->findOneIdByClassName($tabData['class_name']);
        $tab = new Tab($tabId);

        if (false === Validate::isLoadedObject($tab)) {
            return false;
        }

        if (false === $tab->delete()) {
            return false;
        }

        if (isset($tabData['core_reference'])) {
            $tabCoreId = $this->tabRepository->findOneIdByClassName($tabData['core_reference']);
            $tabCore = new Tab($tabCoreId);

            if (Validate::isLoadedObject($tabCore)) {
                $tabCore->active = true;
            }

            if (false === $tabCore->save()) {
                return false;
            }
        }

        return true;
    }
}
