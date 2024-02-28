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

namespace PrestaShop\Module\Mbo\Module\CommandHandler;

use Exception;
use PrestaShop\Module\Mbo\Addons\Exception\DownloadModuleException;
use PrestaShop\Module\Mbo\Helpers\ModuleErrorHelper;
use PrestaShop\Module\Mbo\Module\ActionsManager;
use PrestaShop\Module\Mbo\Module\Command\ModuleStatusTransitionCommand;
use PrestaShop\Module\Mbo\Module\Exception\ModuleNewVersionNotFoundException;
use PrestaShop\Module\Mbo\Module\Exception\TransitionCommandToModuleStatusException;
use PrestaShop\Module\Mbo\Module\Exception\UnauthorizedModuleTransitionException;
use PrestaShop\Module\Mbo\Module\Exception\UnexpectedModuleSourceContentException;
use PrestaShop\Module\Mbo\Module\Module;
use PrestaShop\Module\Mbo\Module\ModuleBuilder;
use PrestaShop\Module\Mbo\Module\Repository;
use PrestaShop\Module\Mbo\Module\TransitionModule;
use PrestaShop\Module\Mbo\Module\ValueObject\ModuleTransitionCommand;
use PrestaShop\Module\Mbo\Module\Workflow\TransitionApplier;
use PrestaShop\Module\Mbo\Module\Workflow\TransitionBuilder;
use PrestaShop\Module\Mbo\Module\Workflow\TransitionInterface;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\SourceHandlerNotFoundException;

final class ModuleStatusTransitionCommandHandler
{
    /**
     * @var Repository
     */
    private $moduleRepository;

    /**
     * @var ActionsManager
     */
    private $actionsManager;

    /**
     * @var ModuleBuilder
     */
    private $moduleBuilder;
    /**
     * @var TransitionBuilder
     */
    private $transitionBuilder;
    /**
     * @var TransitionApplier
     */
    private $transitionApplier;

    public function __construct(
        Repository $moduleRepository,
        ActionsManager $actionsManager,
        ModuleBuilder $moduleBuilder,
        TransitionBuilder $transitionBuilder,
        TransitionApplier $transitionApplier
    ) {
        $this->moduleRepository = $moduleRepository;
        $this->actionsManager = $actionsManager;
        $this->moduleBuilder = $moduleBuilder;
        $this->transitionBuilder = $transitionBuilder;
        $this->transitionApplier = $transitionApplier;
    }

    /**
     * @throws Exception
     */
    public function handle(ModuleStatusTransitionCommand $command): Module
    {
        $moduleName = $command->getModuleName();
        $moduleId = $command->getModuleId();
        $moduleVersion = $command->getModuleVersion();
        $source = $command->getSource();

        // First get the module from DB
        // If not exist, get it from the Module Distribution API
        $dbModule = $this->moduleRepository->findInDatabaseByName($moduleName);

        $module = new TransitionModule(
            $moduleName,
            $dbModule ? $dbModule['version'] : $moduleVersion,
            $dbModule ? $dbModule['installed'] : false,
            $dbModule ? $dbModule['active_on_mobile'] : false,
            $dbModule ? $dbModule['active'] : false
        );

        // Check if transition asked can be mapped to an existing target status
        $transitionCommand = $command->getCommand()->getValue();

        // Download a module before upgrade is not an actual module transition, we do not use the StateMachine
        if (ModuleTransitionCommand::MODULE_COMMAND_DOWNLOAD === $transitionCommand) {
            if (null === $source) {
                $source = $this->actionsManager->downloadModule($moduleId);
            }

            $this->actionsManager->downloadAndReplaceModuleFiles($moduleName, $source);
        } else {
            if (
                !array_key_exists(
                    $transitionCommand,
                    ModuleTransitionCommand::MAPPING_TRANSITION_COMMAND_TARGET_STATUS
                )
            ) {
                throw ModuleErrorHelper::reportAndConvertError(
                    new TransitionCommandToModuleStatusException($command)
                );
            }

            // Compute the state machine transition name
            $transitionName = $this->transitionBuilder->getTransition(
                $module,
                $transitionCommand
            );

            // Do nothing, just return the module
            if ($transitionName === TransitionInterface::NO_CHANGE_TRANSITION) {
                return $this->buildModuleAndReturn($moduleName);
            }

            // Execute the transition
            $this->transitionApplier->apply($module, $transitionName, [
                'source' => $source,
            ]);
        }

        return $this->buildModuleAndReturn($moduleName);
    }

    protected function buildModuleAndReturn(string $moduleName): Module
    {
        $this->moduleRepository->clearCache();

        $stdModule = new \stdClass();
        $stdModule->name = $moduleName;

        return $this->moduleBuilder->build(
            $stdModule,
            $this->moduleRepository->findInDatabaseByName($moduleName)
        );
    }
}
