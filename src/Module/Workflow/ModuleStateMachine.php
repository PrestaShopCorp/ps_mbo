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

namespace PrestaShop\Module\Mbo\Module\Workflow;

use PrestaShop\Module\Mbo\Module\TransitionModule;
use PrestaShop\Module\Mbo\Module\ValueObject\ModuleTransitionCommand;
use PrestaShop\Module\Mbo\Module\Workflow\Exception\UnknownStatusException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\Exception\UndefinedTransitionException;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Validator\StateMachineValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class ModuleStateMachine extends StateMachine
{
    public const MODULE_STATE_MACHINE_NAME = 'module_state_machine';

    public const STATUS_UNINSTALLED = 'uninstalled';
    public const STATUS_ENABLED__MOBILE_ENABLED = 'enabled__mobile_enabled';
    public const STATUS_ENABLED__MOBILE_DISABLED = 'enabled__mobile_disabled';
    public const STATUS_DISABLED__MOBILE_ENABLED = 'disabled__mobile_enabled';
    public const STATUS_DISABLED__MOBILE_DISABLED = 'disabled__mobile_disabled';
    public const STATUS_RESET = 'reset'; //virtual status
    public const STATUS_UPGRADED = 'upgraded'; //virtual status
    public const STATUS_CONFIGURED = 'configured'; //virtual status

    public const STATUSES = [
        self::STATUS_UNINSTALLED,
        self::STATUS_ENABLED__MOBILE_ENABLED,
        self::STATUS_ENABLED__MOBILE_DISABLED,
        self::STATUS_DISABLED__MOBILE_ENABLED,
        self::STATUS_DISABLED__MOBILE_DISABLED,
        self::STATUS_RESET,
        self::STATUS_UPGRADED,
        self::STATUS_CONFIGURED,
    ];

    // From any non-virtual status except UNINSTALLED to RESET
    public const TRANSITION_ENABLED_MOBILE_ENABLED__RESET = 'enabled_and_mobile_enabled_to_reset';
    public const TRANSITION_ENABLED_MOBILE_DISABLED__RESET = 'enabled_and_mobile_disabled_to_reset';
    public const TRANSITION_DISABLED_MOBILE_ENABLED__RESET = 'disabled_and_mobile_enabled_to_reset';
    public const TRANSITION_DISABLED_MOBILE_DISABLED__RESET = 'disabled_and_mobile_disabled_to_reset';

    // From any non-virtual status except UNINSTALLED to CONFIGURED
    public const TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED = 'enabled_and_mobile_enabled_to_configured';
    public const TRANSITION_ENABLED_MOBILE_DISABLED__CONFIGURED = 'enabled_and_mobile_disabled_to_configured';
    public const TRANSITION_DISABLED_MOBILE_ENABLED__CONFIGURED = 'disabled_and_mobile_enabled_to_configured';
    public const TRANSITION_DISABLED_MOBILE_DISABLED__CONFIGURED = 'disabled_and_mobile_disabled_to_configured';

    // From any non-virtual status except UNINSTALLED to UPGRADED
    public const TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED = 'enabled_and_mobile_enabled_to_upgraded';
    public const TRANSITION_ENABLED_MOBILE_DISABLED__UPGRADED = 'enabled_and_mobile_disabled_to_upgraded';
    public const TRANSITION_DISABLED_MOBILE_ENABLED__UPGRADED = 'disabled_and_mobile_enabled_to_upgraded';
    public const TRANSITION_DISABLED_MOBILE_DISABLED__UPGRADED = 'disabled_and_mobile_disabled_to_upgraded';

    // From any non-virtual status except UNINSTALLED to UNINSTALLED
    public const TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED = 'enabled_and_mobile_enabled_to_uninstalled';
    public const TRANSITION_ENABLED_MOBILE_DISABLED__UNINSTALLED = 'enabled_and_mobile_disabled_to_uninstalled';
    public const TRANSITION_DISABLED_MOBILE_ENABLED__UNINSTALLED = 'disabled_and_mobile_enabled_to_uninstalled';
    public const TRANSITION_DISABLED_MOBILE_DISABLED__UNINSTALLED = 'disabled_and_mobile_disabled_to_uninstalled';

    // Transitions from UNINSTALLED
    public const TRANSITION_UNINSTALLED__ENABLED_MOBILE_ENABLED = 'uninstalled_to_enabled_and_mobile_enabled';

    // Transitions from ENABLED__MOBILE_ENABLED
    public const TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED = 'enabled_and_mobile_enabled_to_enabled_and_mobile_disabled';
    public const TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED = 'enabled_and_mobile_enabled_to_disabled_and_mobile_enabled';

    // Transitions from ENABLED__MOBILE_DISABLED
    public const TRANSITION_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED = 'enabled_and_mobile_disabled_to_enabled_and_mobile_enabled';
    public const TRANSITION_ENABLED_MOBILE_DISABLED__DISABLED_MOBILE_DISABLED = 'enabled_and_mobile_disabled_to_disabled_and_mobile_disabled';

    // Transitions from DISABLED__MOBILE_ENABLED
    public const TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED = 'disabled_and_mobile_enabled_to_enabled_and_mobile_enabled';
    public const TRANSITION_DISABLED_MOBILE_ENABLED__DISABLED_MOBILE_DISABLED = 'disabled_and_mobile_enabled_to_disabled_and_mobile_disabled';

    // Transitions from DISABLED__MOBILE_DISABLED
    public const TRANSITION_DISABLED_MOBILE_DISABLED__DISABLED_MOBILE_ENABLED = 'disabled_and_mobile_disabled_to_disabled_and_mobile_enabled';
    public const TRANSITION_DISABLED_MOBILE_DISABLED__ENABLED_MOBILE_DISABLED = 'disabled_and_mobile_disabled_to_enabled_and_mobile_disabled';

    protected $stateMachine;

    public function __construct(EventDispatcherInterface $dispatcher, TranslatorInterface $translator)
    {
        $definitionBuilder = new DefinitionBuilder();
        $definition = $definitionBuilder->addPlaces(self::STATUSES)
            // Transitions are defined with a unique name, an origin place and a destination place

            // From any non-virtual status except UNINSTALLED to RESET
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_ENABLED__RESET, self::STATUS_ENABLED__MOBILE_ENABLED, self::STATUS_RESET))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_DISABLED__RESET, self::STATUS_ENABLED__MOBILE_DISABLED, self::STATUS_RESET))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_ENABLED__RESET, self::STATUS_DISABLED__MOBILE_ENABLED, self::STATUS_RESET))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_DISABLED__RESET, self::STATUS_DISABLED__MOBILE_DISABLED, self::STATUS_RESET))

            // From any non-virtual status except UNINSTALLED to CONFIGURED
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED, self::STATUS_ENABLED__MOBILE_ENABLED, self::STATUS_CONFIGURED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_DISABLED__CONFIGURED, self::STATUS_ENABLED__MOBILE_DISABLED, self::STATUS_CONFIGURED))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_ENABLED__CONFIGURED, self::STATUS_DISABLED__MOBILE_ENABLED, self::STATUS_CONFIGURED))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_DISABLED__CONFIGURED, self::STATUS_DISABLED__MOBILE_DISABLED, self::STATUS_CONFIGURED))

            // From any non-virtual status except UNINSTALLED to UPGRADED
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED, self::STATUS_ENABLED__MOBILE_ENABLED, self::STATUS_UPGRADED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_DISABLED__UPGRADED, self::STATUS_ENABLED__MOBILE_DISABLED, self::STATUS_UPGRADED))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_ENABLED__UPGRADED, self::STATUS_DISABLED__MOBILE_ENABLED, self::STATUS_UPGRADED))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_DISABLED__UPGRADED, self::STATUS_DISABLED__MOBILE_DISABLED, self::STATUS_UPGRADED))

            // From any non-virtual status except UNINSTALLED to UNINSTALLED
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED, self::STATUS_ENABLED__MOBILE_ENABLED, self::STATUS_UNINSTALLED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_DISABLED__UNINSTALLED, self::STATUS_ENABLED__MOBILE_DISABLED, self::STATUS_UNINSTALLED))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_ENABLED__UNINSTALLED, self::STATUS_DISABLED__MOBILE_ENABLED, self::STATUS_UNINSTALLED))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_DISABLED__UNINSTALLED, self::STATUS_DISABLED__MOBILE_DISABLED, self::STATUS_UNINSTALLED))

            // Transitions from UNINSTALLED
            ->addTransition(new Transition(self::TRANSITION_UNINSTALLED__ENABLED_MOBILE_ENABLED, self::STATUS_UNINSTALLED, self::STATUS_ENABLED__MOBILE_ENABLED))

            // Transitions from ENABLED__MOBILE_ENABLED
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED, self::STATUS_ENABLED__MOBILE_ENABLED, self::STATUS_ENABLED__MOBILE_DISABLED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED, self::STATUS_ENABLED__MOBILE_ENABLED, self::STATUS_DISABLED__MOBILE_ENABLED))

            // Transitions from ENABLED__MOBILE_DISABLED
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED, self::STATUS_ENABLED__MOBILE_DISABLED, self::STATUS_ENABLED__MOBILE_ENABLED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_DISABLED__DISABLED_MOBILE_DISABLED, self::STATUS_ENABLED__MOBILE_DISABLED, self::STATUS_DISABLED__MOBILE_DISABLED))

            // Transitions from DISABLED__MOBILE_ENABLED
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED, self::STATUS_DISABLED__MOBILE_ENABLED, self::STATUS_ENABLED__MOBILE_ENABLED))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_ENABLED__DISABLED_MOBILE_DISABLED, self::STATUS_DISABLED__MOBILE_ENABLED, self::STATUS_DISABLED__MOBILE_DISABLED))

            // Transitions from DISABLED__MOBILE_DISABLED
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_DISABLED__DISABLED_MOBILE_ENABLED, self::STATUS_DISABLED__MOBILE_DISABLED, self::STATUS_DISABLED__MOBILE_ENABLED))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_DISABLED__ENABLED_MOBILE_DISABLED, self::STATUS_DISABLED__MOBILE_DISABLED, self::STATUS_ENABLED__MOBILE_DISABLED))

            ->build()
        ;

        $validator = new StateMachineValidator();
        // Throws InvalidDefinitionException in case of an invalid definition
        $validator->validate($definition, self::MODULE_STATE_MACHINE_NAME);

        $singleState = true; // true if the subject can be in only one state at a given time
        $property = 'status'; // subject property name where the state is stored
        $markingStore = new MarkingStore($translator, $singleState, $property);

        parent::__construct($definition, $markingStore, $dispatcher, self::MODULE_STATE_MACHINE_NAME);
    }

    public function getTransition(TransitionModule $module, string $transitionCommand): string
    {
        $originStatus = $module->getStatus();

        switch ($transitionCommand) {
            case ModuleTransitionCommand::MODULE_COMMAND_ENABLE:
                $targetStatus = $module->isActiveOnMobile() ? ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED : ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED;
                break;
            case ModuleTransitionCommand::MODULE_COMMAND_DISABLE:
                $targetStatus = $module->isActiveOnMobile() ? ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED : ModuleStateMachine::STATUS_DISABLED__MOBILE_DISABLED;
                break;
            case ModuleTransitionCommand::MODULE_COMMAND_MOBILE_ENABLE:
                $targetStatus = $module->isActive() ? ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED : ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED;
                break;
            case ModuleTransitionCommand::MODULE_COMMAND_MOBILE_DISABLE:
                $targetStatus = $module->isActive() ? ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED : ModuleStateMachine::STATUS_DISABLED__MOBILE_DISABLED;
                break;
            default:
                $targetStatus = ModuleTransitionCommand::MAPPING_TRANSITION_COMMAND_TARGET_STATUS[$transitionCommand];
        }

        if (!in_array($targetStatus, self::STATUSES)) {
            throw new UnknownStatusException();
        }

        $transitionName = mb_strtolower(sprintf(
            '%s_to_%s',
            str_replace('__', '_and_', ltrim($originStatus, 'STATUS_')),
            str_replace('__', '_and_', ltrim($targetStatus, 'STATUS_'))
        ));

        $enabledTransitions = $this->getEnabledTransitions($module);

        if (null === $this->searchTransitionByName($transitionName, $enabledTransitions)) {
            throw new UndefinedTransitionException($module, $transitionName, $this);
        }

        return $transitionName;
    }

    private function searchTransitionByName(string $transitionName, array $transitions): ?Transition
    {
        /**
         * @var Transition $transition
         */
        foreach ($transitions as $transition) {
            if ($transitionName === $transition->getName()) {
                return $transition;
            }
        }

        return null;
    }
}
