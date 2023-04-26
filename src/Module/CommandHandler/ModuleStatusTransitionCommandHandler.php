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
use PrestaShop\Module\Mbo\Module\Exception\ModuleNotFoundException;
use PrestaShop\Module\Mbo\Module\Exception\TransitionCommandToModuleStatusException;
use PrestaShop\Module\Mbo\Module\Exception\TransitionFailedException;
use PrestaShop\Module\Mbo\Module\Exception\UnauthorizedModuleTransitionException;
use PrestaShop\Module\Mbo\Module\Exception\UnexpectedModuleSourceContentException;
use PrestaShop\Module\Mbo\Module\Module;
use PrestaShop\Module\Mbo\Module\Repository;
use PrestaShop\Module\Mbo\Module\TransitionModule;
use PrestaShop\Module\Mbo\Module\ValueObject\ModuleTransitionCommand;
use PrestaShop\Module\Mbo\Module\Workflow\ModuleStateMachine;

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

    public function __construct(
        ModuleStateMachine $moduleStateMachine,
        Repository $moduleRepository,
        ActionsManager $actionsManager
    ) {
        $this->moduleStateMachine = $moduleStateMachine;
        $this->moduleRepository = $moduleRepository;
        $this->actionsManager = $actionsManager;
    }

    /**
     * @throws UnexpectedModuleSourceContentException
     * @throws ModuleNewVersionNotFoundException
     * @throws ModuleNotFoundException
     * @throws UnauthorizedModuleTransitionException
     * @throws TransitionCommandToModuleStatusException
     * @throws TransitionFailedException
     */
    public function handle(ModuleStatusTransitionCommand $command): Module
    {
        $apiModule = null;
        $moduleName = $command->getModuleName();
        $source = $command->getSource();

        // First get the module from DB
        // If not exist, get it from the Module Distribution API
        $dbModule = $this->moduleRepository->findInDatabaseByName($moduleName);

        if (null !== $dbModule) {
            $module = new TransitionModule(
                $moduleName,
                $dbModule['version'],
                $dbModule['installed'],
                $dbModule['active_on_mobile'],
                $dbModule['active']
            );
        } else {
            $apiModule = $this->moduleRepository->getApiModule($moduleName);

            if (null === $apiModule) {
                throw new ModuleNotFoundException(sprintf('Module %s not found', $moduleName));
            }

            $module = new TransitionModule(
                $moduleName,
                $apiModule->version,
                false,
                false,
                false
            );
        }

        // Check if transition asked can be mapped to an existing target status
        $transitionCommand = $command->getCommand()->getValue();

        // Download a module before upgrade is not an actual module transition, so it cannot be handled by the StateMachine
        if (ModuleTransitionCommand::MODULE_COMMAND_DOWNLOAD === $transitionCommand) {
            $module = $apiModule ?? $this->moduleRepository->getApiModule($moduleName);
            if (null === $module) {
                throw new ModuleNotFoundException(sprintf('Module %s not found', $moduleName));
            }

            $this->actionsManager->downloadAndReplaceModuleFiles($module, $source);
        } else {
            if (!array_key_exists($transitionCommand, ModuleTransitionCommand::MAPPING_TRANSITION_COMMAND_TARGET_STATUS)) {
                throw new TransitionCommandToModuleStatusException(sprintf('Unable to map module transition command given %s', $transitionCommand));
            }

            // Compute the state machine transition name
            $transitionName = $this->moduleStateMachine->getTransition(
                $module,
                $transitionCommand
            );

            // Check if the transition asked is possible
            if (!$this->moduleStateMachine->can($module, $transitionName)) {
                throw new UnauthorizedModuleTransitionException(sprintf('Transition "%s" is not possible for module "%s"', $transitionCommand, $moduleName));
            }

            // Execute the transition
            $this->moduleStateMachine->apply($module, $transitionName, [
                'source' => $source,
            ]);
        }

        $this->moduleRepository->clearCache();

        return $this->moduleRepository->getModule($moduleName);
    }
}
