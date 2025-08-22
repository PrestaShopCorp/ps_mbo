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

function deleteFolderRecursively(string $folderPath): void
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
            deleteFolderRecursively($filePath);
        } else {
            safeUnlink($filePath);
        }
    }

    @rmdir($folderPath);
}

function safeUnlink(string $filePath): void
{
    if (file_exists($filePath) && is_file($filePath)) {
        @unlink($filePath);
    }
}

/**
 * @return bool
 */
function upgrade_module_4_14_0()
{
    $moduleDir = _PS_MODULE_DIR_ . 'ps_mbo';

    try {
        deleteFolderRecursively($moduleDir . '/src/EventSubscriber');
        deleteFolderRecursively($moduleDir . '/src/ExternalContentProvider');
        deleteFolderRecursively($moduleDir . '/src/RecommendedLink');
        deleteFolderRecursively($moduleDir . '/src/RecommendedModule');
        deleteFolderRecursively($moduleDir . '/views/templates/admin/controllers/module_catalog/Includes');

        safeUnlink($moduleDir . '/autoload.php');
        safeUnlink($moduleDir . '/config/services/eventbus.yml');
        safeUnlink($moduleDir . '/config/services/http_clients.yml');
        safeUnlink($moduleDir . '/gha-creds-8ca2dae6482597be.json');
        safeUnlink($moduleDir . '/src/Addons/AddonsDataProvider.php');
        safeUnlink($moduleDir . '/src/AddonsSelectionLinkProvider.php');
        safeUnlink($moduleDir . '/src/ModuleCollectionDataProvider.php');
        safeUnlink($moduleDir . '/src/Addons/User/CredentialsEncryptor.php');
        safeUnlink($moduleDir . '/src/Controller/Admin/ModuleController.php');
        safeUnlink($moduleDir . '/src/Controller/Admin/SecurityController.php');
        safeUnlink($moduleDir . '/src/Distribution/AuthenticationProvider.php');
        safeUnlink($moduleDir . '/src/UpgradeTracker.php');
        safeUnlink($moduleDir . '/translations/de.php');
        safeUnlink($moduleDir . '/translations/en.php');
        safeUnlink($moduleDir . '/translations/es.php');
        safeUnlink($moduleDir . '/translations/fr.php');
        safeUnlink($moduleDir . '/translations/it.php');
        safeUnlink($moduleDir . '/translations/nl.php');
        safeUnlink($moduleDir . '/translations/pl.php');
        safeUnlink($moduleDir . '/translations/pt.php');
        safeUnlink($moduleDir . '/translations/ro.php');
        safeUnlink($moduleDir . '/translations/ru.php');
        safeUnlink($moduleDir . '/views/css/catalog.css');
        safeUnlink($moduleDir . '/views/css/recommended-modules-lower-1.7.8.css');
        safeUnlink($moduleDir . '/views/css/recommended-modules-since-1.7.8.css');
        safeUnlink($moduleDir . '/views/img/no_result.svg');
        safeUnlink($moduleDir . '/views/templates/admin/controllers/module_catalog/catalog_grid.html.twig');
        safeUnlink($moduleDir . '/views/templates/admin/controllers/module_catalog/uninstalled-modules.html.twig');

        return true;
    } catch (Exception $e) {
        return true;
    }
}
