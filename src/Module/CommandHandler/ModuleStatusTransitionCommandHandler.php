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

use PrestaShop\Module\Mbo\Module\Command\ModuleStatusTransitionCommand;
use PrestaShop\Module\Mbo\Module\Exception\ModuleNotFoundException;
use PrestaShop\Module\Mbo\Module\Exception\TransitionCommandToModuleStatusException;
use PrestaShop\Module\Mbo\Module\Exception\UnauthorizedModuleTransitionException;
use PrestaShop\Module\Mbo\Module\Module;
use PrestaShop\Module\Mbo\Module\Repository;
use PrestaShop\Module\Mbo\Module\TransitionModule;
use PrestaShop\Module\Mbo\Module\ValueObject\ModuleTransitionCommand;
use PrestaShop\Module\Mbo\Module\Workflow\ModuleStateMachine;

final class ModuleStatusTransitionCommandHandler
{
    private const MAPPING_TRANSITION_COMMAND_TARGET_STATUS = [
        ModuleTransitionCommand::MODULE_COMMAND_INSTALL => ModuleStateMachine::STATUS_INSTALLED,
        ModuleTransitionCommand::MODULE_COMMAND_ENABLE => ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED,
        ModuleTransitionCommand::MODULE_COMMAND_DISABLE => ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED,
        ModuleTransitionCommand::MODULE_COMMAND_MOBILE_ENABLE => ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED,
        ModuleTransitionCommand::MODULE_COMMAND_MOBILE_DISABLE => ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED,
        ModuleTransitionCommand::MODULE_COMMAND_CONFIGURE => ModuleStateMachine::STATUS_CONFIGURED,
        ModuleTransitionCommand::MODULE_COMMAND_RESET => ModuleStateMachine::STATUS_RESET,
        ModuleTransitionCommand::MODULE_COMMAND_UPGRADE => ModuleStateMachine::STATUS_UPGRADED,
        ModuleTransitionCommand::MODULE_COMMAND_UNINSTALL => ModuleStateMachine::STATUS_UNINSTALLED,
    ];

    /**
     * @var ModuleStateMachine
     */
    private $moduleStateMachine;

    /**
     * @var Repository
     */
    private $moduleRepository;

    public function __construct(
        ModuleStateMachine $moduleStateMachine,
        Repository $moduleRepository
    ) {
        $this->moduleStateMachine = $moduleStateMachine;
        $this->moduleRepository = $moduleRepository;
    }

    public function handle(ModuleStatusTransitionCommand $command): Module
    {
        $moduleName = $command->getModuleName();
        $source = $command->getSource();

        // First get the module from DB and don't go further if it doesn't exist
        $moduleData = $this->moduleRepository->findInDatabaseByName($moduleName);

        if (null === $moduleData) {
            throw new ModuleNotFoundException(sprintf('Module %s not found', $moduleName));
        }
        $module = new TransitionModule(
            $moduleName,
            $moduleData['version'],
            $moduleData['installed'],
            $moduleData['active_on_mobile'],
            $moduleData['active']
        );

        // Check if transition asked can be mapped to an existing target status
        $transitionCommand = $command->getCommand()->getValue();
        if (!array_key_exists($transitionCommand, self::MAPPING_TRANSITION_COMMAND_TARGET_STATUS)) {
            throw new TransitionCommandToModuleStatusException(sprintf('Unable to map module transition command given %s', $transitionCommand));
        }

        // Compute the state machine transition name
        $transitionName = $this->moduleStateMachine->getTransition(
            $module,
            self::MAPPING_TRANSITION_COMMAND_TARGET_STATUS[$transitionCommand]
        );

        // Check if the transition asked is possible
        if (!$this->moduleStateMachine->can($module, $transitionName)) {
            throw new UnauthorizedModuleTransitionException(sprintf('Transition "%s" is not possible for module "%s"', $transitionCommand, $moduleName));
        }

        // Execute the transition
        $this->moduleStateMachine->apply($module, $transitionName, [
            'source' => $source,
        ]);

        return $this->moduleRepository->getModule($moduleName);
    }
}
