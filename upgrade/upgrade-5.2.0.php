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
if (!defined('_PS_VERSION_')) {
    exit;
}

$rootDir = defined('_PS_ROOT_DIR_') ? _PS_ROOT_DIR_ : getenv('_PS_ROOT_DIR_');
if (!$rootDir) {
    $rootDir = __DIR__ . '/../../../';
}

require_once $rootDir . '/vendor/autoload.php';

if (!function_exists('mboUpgradeDeleteFolderRecursively')) {
    function mboUpgradeDeleteFolderRecursively(string $folderPath): void
    {
        if (!is_dir($folderPath)) {
            return;
        }

        $files = @scandir($folderPath);
        if ($files === false) {
            return;
        }

        $files = array_diff($files, ['.', '..']);

        foreach ($files as $file) {
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;

            if (is_dir($filePath)) {
                mboUpgradeDeleteFolderRecursively($filePath);
            } else {
                mboUpgradeSafeUnlink($filePath);
            }
        }

        @rmdir($folderPath);
    }
}
if (!function_exists('mboUpgradeSafeUnlink')) {
    function mboUpgradeSafeUnlink(string $filePath): void
    {
        if (file_exists($filePath) && is_file($filePath)) {
            @unlink($filePath);
        }
    }
}

/**
 * @param ps_mbo $module
 *
 * @return bool
 */
function upgrade_module_5_2_0(Module $module): bool
{
    $moduleDir = _PS_MODULE_DIR_ . 'ps_mbo';

    try {
        $module->updateHooks();
        mboUpgradeDeleteFolderRecursively($moduleDir . '/src/Tab');

        // Remove individual obsolete files
        mboUpgradeSafeUnlink($moduleDir . '/config/services/tab.yml');
        mboUpgradeSafeUnlink($moduleDir . '/src/Controller/Admin/ModuleSelectionController.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Traits/Hooks/UseActionAdminControllerSetMedia.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Traits/Hooks/UseActionGetAdminToolbarButtons.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Traits/Hooks/UseDisplayAdminAfterHeader.php');
        mboUpgradeSafeUnlink($moduleDir . '/views/css/connection-toolbar.css');
        mboUpgradeSafeUnlink($moduleDir . '/views/css/module-catalog.css');
        mboUpgradeSafeUnlink($moduleDir . '/views/css/mbo-user-explanation.css');
        mboUpgradeSafeUnlink($moduleDir . '/views/js/connection-toolbar.js');
        mboUpgradeSafeUnlink($moduleDir . '/views/js/mbo-user-explanation.js');
        mboUpgradeSafeUnlink($moduleDir . '/views/templates/hook/configure-toolbar.tpl');
        mboUpgradeSafeUnlink($moduleDir . '/views/templates/hook/dashboard-zone-two.tpl');
        mboUpgradeSafeUnlink($moduleDir . '/views/templates/hook/twig/explanation_mbo_employee.html.twig');
        mboUpgradeSafeUnlink($moduleDir . '/views/templates/hook/twig/failed-api-user.html.twig');

        return true;
    } catch (Exception $e) {
        return true;
    }
}
