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
 * @return bool
 */
function upgrade_module_4_14_0()
{
    $moduleDir = _PS_MODULE_DIR_ . 'ps_mbo';

    try {
        mboUpgradeDeleteFolderRecursively($moduleDir . '/src/EventSubscriber');
        mboUpgradeDeleteFolderRecursively($moduleDir . '/src/ExternalContentProvider');
        mboUpgradeDeleteFolderRecursively($moduleDir . '/src/RecommendedLink');
        mboUpgradeDeleteFolderRecursively($moduleDir . '/src/RecommendedModule');
        mboUpgradeDeleteFolderRecursively($moduleDir . '/views/templates/admin/controllers/module_catalog/Includes');

        mboUpgradeSafeUnlink($moduleDir . '/autoload.php');
        mboUpgradeSafeUnlink($moduleDir . '/config/services/eventbus.yml');
        mboUpgradeSafeUnlink($moduleDir . '/config/services/http_clients.yml');
        mboUpgradeSafeUnlink($moduleDir . '/gha-creds-8ca2dae6482597be.json');
        mboUpgradeSafeUnlink($moduleDir . '/src/Addons/AddonsDataProvider.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/AddonsSelectionLinkProvider.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/ModuleCollectionDataProvider.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Addons/User/CredentialsEncryptor.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Controller/Admin/ModuleController.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Controller/Admin/SecurityController.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Distribution/AuthenticationProvider.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/UpgradeTracker.php');
        mboUpgradeSafeUnlink($moduleDir . '/translations/de.php');
        mboUpgradeSafeUnlink($moduleDir . '/translations/en.php');
        mboUpgradeSafeUnlink($moduleDir . '/translations/es.php');
        mboUpgradeSafeUnlink($moduleDir . '/translations/fr.php');
        mboUpgradeSafeUnlink($moduleDir . '/translations/it.php');
        mboUpgradeSafeUnlink($moduleDir . '/translations/nl.php');
        mboUpgradeSafeUnlink($moduleDir . '/translations/pl.php');
        mboUpgradeSafeUnlink($moduleDir . '/translations/pt.php');
        mboUpgradeSafeUnlink($moduleDir . '/translations/ro.php');
        mboUpgradeSafeUnlink($moduleDir . '/translations/ru.php');
        mboUpgradeSafeUnlink($moduleDir . '/views/css/catalog.css');
        mboUpgradeSafeUnlink($moduleDir . '/views/css/recommended-modules-lower-1.7.8.css');
        mboUpgradeSafeUnlink($moduleDir . '/views/css/recommended-modules-since-1.7.8.css');
        mboUpgradeSafeUnlink($moduleDir . '/views/img/no_result.svg');
        mboUpgradeSafeUnlink($moduleDir . '/views/templates/admin/controllers/module_catalog/catalog_grid.html.twig');
        mboUpgradeSafeUnlink($moduleDir . '/views/templates/admin/controllers/module_catalog/uninstalled-modules.html.twig');

        return true;
    } catch (Exception $e) {
        return true;
    }
}
