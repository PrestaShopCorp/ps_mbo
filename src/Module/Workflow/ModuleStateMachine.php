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

use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Validator\StateMachineValidator;

class ModuleStateMachine extends StateMachine
{
    public const MODULE_STATE_MACHINE_NAME = 'module_state_machine';

    public const STATUS_INSTALLED = 'installed';
    public const STATUS_UNINSTALLED = 'uninstalled';
    public const STATUS_ENABLED__MOBILE_ENABLED = 'enabled__mobile_enabled';
    public const STATUS_ENABLED__MOBILE_DISABLED = 'enabled__mobile__disabled';
    public const STATUS_DISABLED__MOBILE_ENABLED = 'disabled__mobile_enabled';
    public const STATUS_RESET = 'reset'; //virtual status
    public const STATUS_UPGRADED = 'upgraded'; //virtual status
    public const STATUS_CONFIGURED = 'configured'; //virtual status

    public const TRANSITION_INSTALLED__ENABLED_MOBILE_DISABLED = 'installed__enabled_mobile_disabled';
    public const TRANSITION_INSTALLED__DISABLED_MOBILE_ENABLED = 'installed__disabled_mobile_enabled';
    public const TRANSITION_INSTALLED__RESET = 'installed__reset';
    public const TRANSITION_INSTALLED__CONFIGURED = 'installed__configured';
    public const TRANSITION_INSTALLED__UPGRADED = 'installed__upgraded';
    public const TRANSITION_INSTALLED__UNINSTALLED = 'installed__uninstalled';

    public const TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED = 'enabled_mobile_enabled__enabled_mobile_disabled';
    public const TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED = 'enabled_mobile_enabled__disabled_mobile_enabled';
    public const TRANSITION_ENABLED_MOBILE_ENABLED__RESET = 'enabled_mobile_enabled__reset';
    public const TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED = 'enabled_mobile_enabled__upgraded';
    public const TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED = 'enabled_mobile_enabled__configured';
    public const TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED = 'enabled_mobile_enabled__uninstalled';

    public const TRANSITION_ENABLED_MOBILE_DISABLED__INSTALLED = 'enabled_mobile_disabled__installed';
    public const TRANSITION_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED = 'enabled_mobile_disabled__enabled_mobile_enabled';
    public const TRANSITION_ENABLED_MOBILE_DISABLED__RESET = 'enabled_mobile_disabled__reset';
    public const TRANSITION_ENABLED_MOBILE_DISABLED__UPGRADED = 'enabled_mobile_disabled__upgraded';
    public const TRANSITION_ENABLED_MOBILE_DISABLED__CONFIGURED = 'enabled_mobile_disabled__configured';
    public const TRANSITION_ENABLED_MOBILE_DISABLED__UNINSTALLED = 'enabled_mobile_disabled__uninstalled';

    public const TRANSITION_DISABLED_MOBILE_ENABLED__INSTALLED = 'disabled_mobile_enabled__installed';
    public const TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED = 'disabled_mobile_enabled__enabled_mobile_enabled';
    public const TRANSITION_DISABLED_MOBILE_ENABLED__RESET = 'disabled_mobile_enabled__reset';
    public const TRANSITION_DISABLED_MOBILE_ENABLED__UPGRADED = 'disabled_mobile_enabled__upgraded';
    public const TRANSITION_DISABLED_MOBILE_ENABLED__CONFIGURED = 'disabled_mobile_enabled__configured';
    public const TRANSITION_DISABLED_MOBILE_ENABLED__UNINSTALLED = 'disabled_mobile_enabled__uninstalled';

    public const TRANSITION_UNINSTALLED__INSTALLED = 'uninstalled__installed';

    protected $stateMachine;

    public function __construct()
    {
        $definitionBuilder = new DefinitionBuilder();
        $definition = $definitionBuilder->addPlaces([
            self::STATUS_INSTALLED,
            self::STATUS_UNINSTALLED,
            self::STATUS_ENABLED__MOBILE_ENABLED,
            self::STATUS_ENABLED__MOBILE_DISABLED,
            self::STATUS_DISABLED__MOBILE_ENABLED,
            self::STATUS_RESET,
            self::STATUS_UPGRADED,
            self::STATUS_CONFIGURED,
        ])
            // Transitions are defined with a unique name, an origin place and a destination place
            ->addTransition(new Transition(self::TRANSITION_INSTALLED__ENABLED_MOBILE_DISABLED, self::STATUS_INSTALLED, self::STATUS_ENABLED__MOBILE_DISABLED))
            ->addTransition(new Transition(self::TRANSITION_INSTALLED__DISABLED_MOBILE_ENABLED, self::STATUS_INSTALLED, self::STATUS_DISABLED__MOBILE_ENABLED))
            ->addTransition(new Transition(self::TRANSITION_INSTALLED__CONFIGURED, self::STATUS_INSTALLED, self::STATUS_CONFIGURED))
            ->addTransition(new Transition(self::TRANSITION_INSTALLED__RESET, self::STATUS_INSTALLED, self::STATUS_RESET))
            ->addTransition(new Transition(self::TRANSITION_INSTALLED__UPGRADED, self::STATUS_INSTALLED, self::STATUS_UPGRADED))
            ->addTransition(new Transition(self::TRANSITION_INSTALLED__UNINSTALLED, self::STATUS_INSTALLED, self::STATUS_UNINSTALLED))

            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED, self::STATUS_ENABLED__MOBILE_ENABLED, self::STATUS_ENABLED__MOBILE_DISABLED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED, self::STATUS_ENABLED__MOBILE_ENABLED, self::STATUS_DISABLED__MOBILE_ENABLED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED, self::STATUS_ENABLED__MOBILE_ENABLED, self::STATUS_CONFIGURED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_ENABLED__RESET, self::STATUS_ENABLED__MOBILE_ENABLED, self::STATUS_RESET))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED, self::STATUS_ENABLED__MOBILE_ENABLED, self::STATUS_UPGRADED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED, self::STATUS_ENABLED__MOBILE_ENABLED, self::STATUS_UNINSTALLED))

            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_DISABLED__INSTALLED, self::STATUS_ENABLED__MOBILE_DISABLED, self::STATUS_INSTALLED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED, self::STATUS_ENABLED__MOBILE_DISABLED, self::STATUS_ENABLED__MOBILE_ENABLED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_DISABLED__CONFIGURED, self::STATUS_ENABLED__MOBILE_DISABLED, self::STATUS_CONFIGURED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_DISABLED__RESET, self::STATUS_ENABLED__MOBILE_DISABLED, self::STATUS_RESET))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_DISABLED__UPGRADED, self::STATUS_ENABLED__MOBILE_DISABLED, self::STATUS_UPGRADED))
            ->addTransition(new Transition(self::TRANSITION_ENABLED_MOBILE_DISABLED__UNINSTALLED, self::STATUS_ENABLED__MOBILE_DISABLED, self::STATUS_UNINSTALLED))

            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_ENABLED__INSTALLED, self::STATUS_DISABLED__MOBILE_ENABLED, self::STATUS_INSTALLED))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED, self::STATUS_DISABLED__MOBILE_ENABLED, self::STATUS_ENABLED__MOBILE_ENABLED))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_ENABLED__CONFIGURED, self::STATUS_DISABLED__MOBILE_ENABLED, self::STATUS_CONFIGURED))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_ENABLED__RESET, self::STATUS_DISABLED__MOBILE_ENABLED, self::STATUS_RESET))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_ENABLED__UPGRADED, self::STATUS_DISABLED__MOBILE_ENABLED, self::STATUS_UPGRADED))
            ->addTransition(new Transition(self::TRANSITION_DISABLED_MOBILE_ENABLED__UNINSTALLED, self::STATUS_DISABLED__MOBILE_ENABLED, self::STATUS_UNINSTALLED))

            ->addTransition(new Transition(self::TRANSITION_UNINSTALLED__INSTALLED, self::STATUS_UNINSTALLED, self::STATUS_INSTALLED))
            ->build()
        ;

        $validator = new StateMachineValidator();
        // Throws InvalidDefinitionException in case of an invalid definition
        $validator->validate($definition, self::MODULE_STATE_MACHINE_NAME);

        $singleState = true; // true if the subject can be in only one state at a given time
        $property = 'status'; // subject property name where the state is stored
        $markingStore = new MethodMarkingStore($singleState, $property);

        parent::__construct($definition, $markingStore, null, self::MODULE_STATE_MACHINE_NAME);
    }
}
