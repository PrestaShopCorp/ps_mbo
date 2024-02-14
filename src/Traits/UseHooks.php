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
use PrestaShopDatabaseException;
use Symfony\Component\String\UnicodeString;

trait UseHooks
{
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayBackOfficeEmployeeMenu;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneOne;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneTwo {
        \PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneOne::smartyDisplayTpl insteadof \PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneTwo;
        \PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneOne::loadCdcMediaFilesForControllers insteadof \PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneTwo;
    }
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneThree {
        \PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneOne::smartyDisplayTpl insteadof \PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneThree;
        \PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneOne::loadCdcMediaFilesForControllers insteadof \PrestaShop\Module\Mbo\Traits\Hooks\UseDashboardZoneThree;
    }
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayAdminThemesListAfter;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayDashboardTop;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseActionAdminControllerSetMedia;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseActionBeforeInstallModule;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseActionGetAdminToolbarButtons;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseActionGetAlternativeSearchPanels;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayAdminAfterHeader;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayModuleConfigureExtraButtons;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseActionListModules;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseActionModuleRegisterHookAfter;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseDisplayEmptyModuleCategoryExtraMessage;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseActionDispatcherBefore;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseActionObjectShopUrlUpdateAfter;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseActionGeneralPageSave;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseActionBeforeUpgradeModule;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseActionObjectEmployeeDeleteBefore;
    use \PrestaShop\Module\Mbo\Traits\Hooks\UseActionObjectEmployeeUpdateBefore;

    /**
     * @var array An array of method that can be called to register media in the actionAdminControllerSetMedia hook
     *
     * @see UseActionAdminControllerSetMedia
     */
    protected $adminControllerMediaMethods = [];

    /**
     * Try to call the "bootHookClassName" method on each hook class.
     *
     * @return void
     */
    protected function bootHooks(): void
    {
        foreach ($this->getTraitNames() as $traitName) {
            if (method_exists($this, "boot{$traitName}")) {
                $this->{"boot{$traitName}"}();
            }
        }
    }

    /**
     * Try to call the "hookClassNameExtraOperations" method on each hook class.
     *
     * @return void
     */
    protected function installHooks(): void
    {
        foreach ($this->getTraitNames() as $traitName) {
            $traitName = lcfirst($traitName);
            if (method_exists($this, "{$traitName}ExtraOperations")) {
                $this->{"{$traitName}ExtraOperations"}();
            }
        }
    }

    /**
     * Guess the hooks names by using the traits names. Remove the "Use" in the traits name.
     *
     * @return string[]
     */
    protected function getHooksNames(): array
    {
        return array_map(function ($trait) {
            return str_replace('Use', '', $trait);
        }, $this->getTraitNames());
    }

    /**
     * Parse all classes used by this trait, and extract them
     *
     * @return string[]
     */
    protected function getTraitNames(): array
    {
        $traits = [];
        //Retrieve all used classes and iterate
        foreach (class_uses(UseHooks::class) as $trait) {
            //Get only the class name eg. 'UseAdminControllerSetMedia'
            $traits[] = (new UnicodeString($trait))->afterLast('\\')->toString();
        }

        return $traits;
    }

    /**
     * Update hooks in DB.
     * Search current hooks registered in DB and compare them with the hooks declared in the module.
     * If a hook is missing, it will be added. If a hook is not declared in the module, it will be removed.
     *
     * @return void
     * @throws PrestaShopDatabaseException
     */
    public function updateHooks(): void
    {
        $hookData = Db::getInstance()->executeS('
            SELECT DISTINCT(phm.id_hook), name
            FROM `' . _DB_PREFIX_ . 'hook_module` phm
            JOIN `' . _DB_PREFIX_ . 'hook` ph ON ph.id_hook=phm.id_hook
            WHERE `id_module` = ' . (int) $this->id
        );

        $currentModuleHooks = $this->getHooksNames();

        $oldHooks = [];
        $newHooks = [];

        // Iterate on DB hooks to get only the old ones
        foreach ($hookData as $hook) {
            if (!in_array(strtolower($hook['name']), array_map('strtolower', $currentModuleHooks))) {
                $oldHooks[] = $hook;
            }
        }

        // Iterate on module hooks to get only the new ones
        foreach ($currentModuleHooks as $moduleHook) {
            $isNew = true;
            foreach ($hookData as $hookInDb) {
                if (strtolower($moduleHook) === strtolower($hookInDb['name'])) {
                    $isNew = false;
                    break;
                }
            }
            if ($isNew) {
                $newHooks[] = $moduleHook;
            }
        }

        foreach ($oldHooks as $oldHook) {
            $this->unregisterHook($oldHook['id_hook']);
        }

        if (!empty($newHooks)) {
            $this->registerHook($newHooks);
            foreach ($newHooks as $newHook) {
                $methodName = "use" . ucfirst($newHook) . "ExtraOperations";
                if (method_exists($this, $methodName)) {
                    $this->$methodName();
                }
            }
        }
    }
}
