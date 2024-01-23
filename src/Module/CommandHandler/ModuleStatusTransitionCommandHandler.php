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
use PrestaShop\Module\Mbo\Module\Workflow\ModuleStateMachine;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\SourceHandlerNotFoundException;

final class ModuleStatusTransitionCommandHandler
{
    /**
     * @var ModuleStateMachine
     */
    private $moduleStateMachine;

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

    public function __construct(
        ModuleStateMachine $moduleStateMachine,
        Repository $moduleRepository,
        ActionsManager $actionsManager,
        ModuleBuilder $moduleBuilder
    ) {
        $this->moduleStateMachine = $moduleStateMachine;
        $this->moduleRepository = $moduleRepository;
        $this->actionsManager = $actionsManager;
        $this->moduleBuilder = $moduleBuilder;
    }

    /**
     * @throws UnexpectedModuleSourceContentException
     * @throws ModuleNewVersionNotFoundException
     * @throws UnauthorizedModuleTransitionException
     * @throws TransitionCommandToModuleStatusException
     * @throws SourceHandlerNotFoundException
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
                throw new TransitionCommandToModuleStatusException(
                    sprintf(
                        'Unable to map module transition command given %s',
                        $transitionCommand
                    )
                );
            }

            // Compute the state machine transition name
            $transitionName = $this->moduleStateMachine->getTransition(
                $module,
                $transitionCommand
            );

            if($transitionName === ModuleStateMachine::NO_CHANGE_TRANSITION) {
                return $this->buildModuleAndReturn($moduleName);
            }

            // Check if the transition asked is possible
            if (!$this->moduleStateMachine->can($module, $transitionName)) {
                throw new UnauthorizedModuleTransitionException(
                    sprintf(
                        'Transition "%s" is not possible for module "%s"',
                        $transitionCommand,
                        $moduleName
                    )
                );
            }

            // Execute the transition
            $this->moduleStateMachine->apply($module, $transitionName, [
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
