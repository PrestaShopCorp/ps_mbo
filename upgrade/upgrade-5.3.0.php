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
function upgrade_module_5_3_0(Module $module): bool
{
    try {
        $moduleDir = _PS_MODULE_DIR_ . 'ps_mbo';

        // Remove legacy inbound API tabs (ApiPsMbo, ApiSecurityPsMbo) from ps_tab.
        // updateTabs() diffs DB against $ADMIN_CONTROLLERS — tabs no longer declared are deleted.
        $module->updateTabs();

        // Clear cached JWTs: the token salt changed (no longer tied to ApiPsMbo tab id).
        $authProvider = $module->get(\PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider::class);
        if (null !== $authProvider) {
            $authProvider->clearCache();
        }

        // Delete PHP class files removed in v5.3.0. The autoupgrade module does not remove
        // files absent from the new zip, so stale classes must be explicitly deleted here.
        // The YAML service files in config/services/api/ are kept (emptied) in the new zip
        // so the DI container does not attempt to instantiate the deleted classes.

        // Legacy inbound API controllers
        mboUpgradeSafeUnlink($moduleDir . '/controllers/admin/apiPsMbo.php');
        mboUpgradeSafeUnlink($moduleDir . '/controllers/admin/apiSecurityPsMbo.php');

        // src/Api/ — all PHP class files (AdminAuthenticationProvider.php is kept)
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/index.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Config/Config.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Config/Env.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Config/index.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Controller/AbstractAdminApiController.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Controller/index.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Exception/IncompleteSignatureParamsException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Exception/QueryParamsException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Exception/RetrieveNewKeyException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Exception/UnauthorizedException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Exception/UnknownServiceException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Exception/index.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Repository/ModuleRepository.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Repository/index.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Security/AuthorizationChecker.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Security/index.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Service/ConfigApplyExecutor.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Service/Factory.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Service/ModuleTransitionExecutor.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Service/ServiceExecutorInterface.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Api/Service/index.php');

        // src/Module/Workflow/ — entire state machine
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Workflow/index.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Workflow/Transition.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Workflow/TransitionApplier.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Workflow/TransitionBuilder.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Workflow/TransitionInterface.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Workflow/TransitionsManager.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Workflow/Exception/NotAllowedTransitionException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Workflow/Exception/UnknownStatusException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Workflow/Exception/UnknownTransitionException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Workflow/Exception/index.php');

        // src/Module/ — command/handler/VO/exceptions only used by the API
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/TransitionModule.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Command/ModuleStatusTransitionCommand.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Command/index.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/CommandHandler/ModuleStatusTransitionCommandHandler.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/CommandHandler/index.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/ValueObject/ModuleTransitionCommand.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/ValueObject/index.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Exception/ModuleNewVersionNotFoundException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Exception/ModuleNotFoundException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Exception/ModuleUpgradeFailedException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Exception/TransitionCommandToModuleStatusException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Exception/TransitionFailedException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Exception/UnauthorizedModuleTransitionException.php');
        mboUpgradeSafeUnlink($moduleDir . '/src/Module/Exception/UnknownModuleTransitionCommandException.php');

        // Orphaned CSS asset
        mboUpgradeSafeUnlink($moduleDir . '/views/css/hide-toolbar.css');

        return true;
    } catch (Exception $e) {
        return true;
    }
}
