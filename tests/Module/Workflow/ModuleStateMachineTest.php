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

namespace PrestaShop\Module\Mbo\Tests\Module\Workflow;

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Mbo\Module\Exception\TransitionFailedException;
use PrestaShop\Module\Mbo\Module\TransitionModule;
use PrestaShop\Module\Mbo\Module\ValueObject\ModuleTransitionCommand;
use PrestaShop\Module\Mbo\Module\Workflow\Event\TransitionEventSubscriber;
use PrestaShop\Module\Mbo\Module\Workflow\ModuleStateMachine;
use PrestaShop\Module\Mbo\Module\Workflow\TransitionsManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Workflow\Transition;
use Symfony\Contracts\Translation\TranslatorInterface;

class ModuleStateMachineTest extends TestCase
{
    private const TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
    ];
    private const TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
    ];
    private const TRANSITION_ENABLED_MOBILE_ENABLED__RESET = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__RESET,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_RESET],
    ];
    private const TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_CONFIGURED],
    ];
    private const TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_UPGRADED],
    ];
    private const TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_UNINSTALLED],
    ];

    private const TRANSITION_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
    ];
    private const TRANSITION_ENABLED_MOBILE_DISABLED__DISABLED_MOBILE_DISABLED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__DISABLED_MOBILE_DISABLED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_DISABLED],
    ];
    private const TRANSITION_ENABLED_MOBILE_DISABLED__RESET = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__RESET,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_RESET],
    ];
    private const TRANSITION_ENABLED_MOBILE_DISABLED__UPGRADED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__UPGRADED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_UPGRADED],
    ];
    private const TRANSITION_ENABLED_MOBILE_DISABLED__CONFIGURED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__CONFIGURED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_CONFIGURED],
    ];
    private const TRANSITION_ENABLED_MOBILE_DISABLED__UNINSTALLED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__UNINSTALLED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_UNINSTALLED],
    ];

    private const TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
    ];
    private const TRANSITION_DISABLED_MOBILE_ENABLED__DISABLED_MOBILE_DISABLED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__DISABLED_MOBILE_DISABLED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_DISABLED],
    ];
    private const TRANSITION_DISABLED_MOBILE_ENABLED__RESET = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__RESET,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_RESET],
    ];
    private const TRANSITION_DISABLED_MOBILE_ENABLED__UPGRADED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__UPGRADED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_UPGRADED],
    ];
    private const TRANSITION_DISABLED_MOBILE_ENABLED__CONFIGURED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__CONFIGURED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_CONFIGURED],
    ];
    private const TRANSITION_DISABLED_MOBILE_ENABLED__UNINSTALLED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__UNINSTALLED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_UNINSTALLED],
    ];

    private const TRANSITION_DISABLED_MOBILE_DISABLED__ENABLED_MOBILE_DISABLED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_DISABLED__ENABLED_MOBILE_DISABLED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
    ];
    private const TRANSITION_DISABLED_MOBILE_DISABLED__DISABLED_MOBILE_ENABLED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_DISABLED__DISABLED_MOBILE_ENABLED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
    ];
    private const TRANSITION_DISABLED_MOBILE_DISABLED__RESET = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_DISABLED__RESET,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_RESET],
    ];
    private const TRANSITION_DISABLED_MOBILE_DISABLED__UPGRADED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_DISABLED__UPGRADED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_UPGRADED],
    ];
    private const TRANSITION_DISABLED_MOBILE_DISABLED__CONFIGURED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_DISABLED__CONFIGURED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_CONFIGURED],
    ];
    private const TRANSITION_DISABLED_MOBILE_DISABLED__UNINSTALLED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_DISABLED__UNINSTALLED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_UNINSTALLED],
    ];

    private const TRANSITION_UNINSTALLED__ENABLED_MOBILE_ENABLED = [
        'name' => ModuleStateMachine::TRANSITION_UNINSTALLED__ENABLED_MOBILE_ENABLED,
        'froms' => [ModuleStateMachine::STATUS_UNINSTALLED],
        'tos' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
    ];

    /**
     * @var ModuleStateMachine
     */
    protected $moduleStateMachine;

    protected $translator;

    protected function setUp(): void
    {
        $eventDispatcher = new EventDispatcher();
        $this->translator = $this->mockTranslator('Unfortunately, the module did not return additional details.', [], 'Admin.Modules.Notification', 'Unfortunately, the module did not return additional details.');
        $this->moduleStateMachine = new ModuleStateMachine($eventDispatcher, $this->translator);
    }

    public function testModuleUninstalledPossibleTransitions()
    {
        $module = $this->getTransitionModule('x_module', '1.0.0', false, false, false);

        $possibleTransitions = $this->moduleStateMachine->getEnabledTransitions($module);

        $this->assertSame(ModuleStateMachine::STATUS_UNINSTALLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_UNINSTALLED__ENABLED_MOBILE_ENABLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    public function testModuleDisabledMobileDisabledPossibleTransitions()
    {
        $module = $this->getTransitionModule('x_module', '1.0.0', true, false, false);

        $possibleTransitions = $this->moduleStateMachine->getEnabledTransitions($module);

        $this->assertSame(ModuleStateMachine::STATUS_DISABLED__MOBILE_DISABLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_DISABLED_MOBILE_DISABLED__RESET,
            self::TRANSITION_DISABLED_MOBILE_DISABLED__CONFIGURED,
            self::TRANSITION_DISABLED_MOBILE_DISABLED__UPGRADED,
            self::TRANSITION_DISABLED_MOBILE_DISABLED__UNINSTALLED,
            self::TRANSITION_DISABLED_MOBILE_DISABLED__DISABLED_MOBILE_ENABLED,
            self::TRANSITION_DISABLED_MOBILE_DISABLED__ENABLED_MOBILE_DISABLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    public function testModuleEnabledMobileDisabledPossibleTransitions()
    {
        $module = $this->getTransitionModule('x_module', '1.0.0', true, false, true);

        $possibleTransitions = $this->moduleStateMachine->getEnabledTransitions($module);

        $this->assertSame(ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_ENABLED_MOBILE_DISABLED__RESET,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__CONFIGURED,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__UPGRADED,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__UNINSTALLED,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__DISABLED_MOBILE_DISABLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    public function testModuleEnabledMobileEnabledPossibleTransitions()
    {
        $module = $this->getTransitionModule('x_module', '1.0.0', true, true, true);

        $possibleTransitions = $this->moduleStateMachine->getEnabledTransitions($module);

        $this->assertSame(ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_ENABLED_MOBILE_ENABLED__RESET,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    public function testModuleDisabledMobileEnabledPossibleTransitions()
    {
        $module = $this->getTransitionModule('x_module', '1.0.0', true, true, false);

        $possibleTransitions = $this->moduleStateMachine->getEnabledTransitions($module);

        $this->assertSame(ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_DISABLED_MOBILE_ENABLED__RESET,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__CONFIGURED,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__UPGRADED,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__UNINSTALLED,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__DISABLED_MOBILE_DISABLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    /**
     * @dataProvider getModuleAttributesAndAppliedTransitions
     */
    public function testApplyTransitions(array $moduleAttributes, string $transitionCommand, string $transitionName, string $targetStatus)
    {
        $module = $this->getTransitionModule(
            $moduleAttributes['name'],
            $moduleAttributes['version'],
            $moduleAttributes['installed'],
            $moduleAttributes['active_on_mobile'],
            $moduleAttributes['active']
        );

        $transitionsManager = $this->createMock(TransitionsManager::class);
        $methodName = (new UnicodeString($transitionName))->camel()->toString();

        $transitionsManager
            ->expects($this->once())
            ->method($methodName)
            ->with($module, $targetStatus, [
                'transitionsManager' => $transitionsManager,
                'method' => $methodName,
            ])
        ;
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(new TransitionEventSubscriber($transitionsManager));
        $moduleStateMachine = new ModuleStateMachine($eventDispatcher, $this->translator);

        $this->assertTrue($moduleStateMachine->can($module, $transitionName));

        // Application will throw an exception because transitionManager object is a mock
        $this->expectException(TransitionFailedException::class);
        $moduleStateMachine->apply($module, $transitionName);
    }

    /**
     * @dataProvider getModuleAttributesAndAppliedTransitions
     */
    public function testGetTransition(array $moduleAttributes, string $transitionCommand, string $transitionName, string $targetStatus)
    {
        $module = $this->getTransitionModule(
            $moduleAttributes['name'],
            $moduleAttributes['version'],
            $moduleAttributes['installed'],
            $moduleAttributes['active_on_mobile'],
            $moduleAttributes['active']
        );

        $this->assertSame($transitionName, $this->moduleStateMachine->getTransition($module, $transitionCommand));
    }

    public function getModuleAttributesAndAppliedTransitions()
    {
        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_MOBILE_DISABLE, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED, //transitionName
            ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_DISABLE, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED, //transitionName
            ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_RESET, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__RESET, //transitionName
            ModuleStateMachine::STATUS_RESET, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_CONFIGURE, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED, //transitionName
            ModuleStateMachine::STATUS_CONFIGURED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UPGRADE, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED, //transitionName
            ModuleStateMachine::STATUS_UPGRADED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UNINSTALL, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED, //transitionName
            ModuleStateMachine::STATUS_UNINSTALLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_MOBILE_DISABLE, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED, //transitionName
            ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_DISABLE, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED, //transitionName
            ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_RESET, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__RESET, //transitionName
            ModuleStateMachine::STATUS_RESET, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UPGRADE, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED, //transitionName
            ModuleStateMachine::STATUS_UPGRADED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_CONFIGURE, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED, //transitionName
            ModuleStateMachine::STATUS_CONFIGURED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UNINSTALL, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED, //transitionName
            ModuleStateMachine::STATUS_UNINSTALLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => false], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_MOBILE_ENABLE, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED, //transitionName
            ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => false], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_RESET, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__RESET, //transitionName
            ModuleStateMachine::STATUS_RESET, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => false], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UPGRADE, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__UPGRADED, //transitionName
            ModuleStateMachine::STATUS_UPGRADED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => false], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_CONFIGURE, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__CONFIGURED, //transitionName
            ModuleStateMachine::STATUS_CONFIGURED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => false], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UNINSTALL, //transition command
            ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__UNINSTALLED, //transitionName
            ModuleStateMachine::STATUS_UNINSTALLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => false, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_ENABLE, //transition command
            ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED, //transitionName
            ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => false, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_ENABLE, //transition command
            ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED, //transitionName
            ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => false, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_RESET, //transition command
            ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__RESET, //transitionName
            ModuleStateMachine::STATUS_RESET, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => false, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UPGRADE, //transition command
            ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__UPGRADED, //transitionName
            ModuleStateMachine::STATUS_UPGRADED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => false, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_CONFIGURE, //transition command
            ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__CONFIGURED, //transitionName
            ModuleStateMachine::STATUS_CONFIGURED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => false, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UNINSTALL, //transition command
            ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__UNINSTALLED, //transitionName
            ModuleStateMachine::STATUS_UNINSTALLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => false, 'active' => false, 'active_on_mobile' => false], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_INSTALL, //transition command
            ModuleStateMachine::TRANSITION_UNINSTALLED__ENABLED_MOBILE_ENABLED, //transitionName
            ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED, //target status
        ];
    }

    private function getTransitionModule(
        string $name,
        string $version,
        bool $installed,
        bool $activeOnMobile,
        bool $active
    ): TransitionModule {
        return new TransitionModule($name, $version, $installed, $activeOnMobile, $active);
    }

    private function transitionsToArray(array $transitions): array
    {
        $convertedTransitions = [];

        /** @var Transition $transition */
        foreach ($transitions as $transition) {
            $convertedTransitions[] = [
                'name' => $transition->getName(),
                'froms' => $transition->getFroms(),
                'tos' => $transition->getTos(),
            ];
        }

        return $convertedTransitions;
    }

    /**
     * Mock translator
     *
     * @param string|array $value
     * @param array $params
     * @param string $domain
     * @param string $returnValue
     */
    private function mockTranslator($value, $params = [], $domain = '', $returnValue = null)
    {
        $translatorMock = \Mockery::mock(TranslatorInterface::class);

        if (is_array($value)) {
            foreach ($value as $val) {
                $translatorMock->shouldReceive('trans')
                    ->with($val[0][0], $val[0][1], $val[0][2])
                    ->andReturn($val[1]);
            }
        } else {
            $translatorMock->shouldReceive('trans')
                ->with($value, $params, $domain)
                ->andReturn($returnValue);
        }

        return $translatorMock;
    }
}
