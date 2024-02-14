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
use PrestaShop\Module\Mbo\Module\Workflow\Exception\NotAllowedTransitionException;
use PrestaShop\Module\Mbo\Module\Workflow\Exception\UnknownStatusException;

class TransitionBuilder
{
    /**
     * @var Transition[]
     */
    private $allowedTransitions;

    public function __construct()
    {
        $this->allowedTransitions = $this->buildAllowedTransitions();
    }

    public function getTransition(TransitionModule $module, string $transitionCommand): string
    {
        $originStatus = $module->getStatus();

        switch ($transitionCommand) {
            case ModuleTransitionCommand::MODULE_COMMAND_ENABLE:
                $targetStatus = $module->isActiveOnMobile()
                    ? TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED
                    : TransitionInterface::STATUS_ENABLED__MOBILE_DISABLED;
                break;
            case ModuleTransitionCommand::MODULE_COMMAND_DISABLE:
                $targetStatus = $module->isActiveOnMobile()
                    ? TransitionInterface::STATUS_DISABLED__MOBILE_ENABLED
                    : TransitionInterface::STATUS_DISABLED__MOBILE_DISABLED;
                break;
            case ModuleTransitionCommand::MODULE_COMMAND_MOBILE_ENABLE:
                $targetStatus = $module->isActive()
                    ? TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED
                    : TransitionInterface::STATUS_DISABLED__MOBILE_ENABLED;
                break;
            case ModuleTransitionCommand::MODULE_COMMAND_MOBILE_DISABLE:
                $targetStatus = $module->isActive()
                    ? TransitionInterface::STATUS_ENABLED__MOBILE_DISABLED
                    : TransitionInterface::STATUS_DISABLED__MOBILE_DISABLED;
                break;
            default:
                $targetStatus = ModuleTransitionCommand::MAPPING_TRANSITION_COMMAND_TARGET_STATUS[$transitionCommand];
        }

        if (!in_array($targetStatus, TransitionInterface::STATUSES)) {
            throw new UnknownStatusException();
        }

        if (
            $originStatus === $targetStatus
            || (
                TransitionInterface::STATUS_UNINSTALLED === $originStatus
                && TransitionInterface::STATUS_DISABLED__MOBILE_DISABLED === $targetStatus
            ) // because uninstalled is the same as disabled_and_mobile_disabled
            || (
                TransitionInterface::STATUS_DISABLED__MOBILE_DISABLED === $originStatus
                && TransitionInterface::STATUS_UNINSTALLED === $targetStatus
            ) // because uninstalled is the same as disabled_and_mobile_disabled
        ) {
            return TransitionInterface::NO_CHANGE_TRANSITION;
        }

        $transitionName = mb_strtolower(sprintf(
            '%s_to_%s',
            str_replace('__', '_and_', ltrim($originStatus, 'STATUS_')),
            str_replace('__', '_and_', ltrim($targetStatus, 'STATUS_'))
        ));

        $enabledTransitions = $this->getModuleAllowedTransitions($module);

        if (null === $this->isTransitionAllowed($transitionName, $enabledTransitions)) {
            throw new NotAllowedTransitionException($module, $originStatus, $targetStatus);
        }

        return $transitionName;
    }

    public function getModuleAllowedTransitions(TransitionModule $module): array
    {
        $moduleStatus = $module->getStatus();

        return array_filter($this->allowedTransitions, function(TransitionInterface $transition) use ($moduleStatus) {
            return 0 === strcasecmp($moduleStatus, $transition->getFromStatus());
        });
    }


    /**
     * @return Transition[]
     */
    private function buildAllowedTransitions(): array
    {
        $allowedTransitions = [
            TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED => [
                TransitionInterface::STATUS_RESET,
                TransitionInterface::STATUS_UPGRADED,
                TransitionInterface::STATUS_CONFIGURED,
                TransitionInterface::STATUS_UNINSTALLED,
                TransitionInterface::STATUS_ENABLED__MOBILE_DISABLED,
                TransitionInterface::STATUS_DISABLED__MOBILE_ENABLED,
            ],
            TransitionInterface::STATUS_ENABLED__MOBILE_DISABLED => [
                TransitionInterface::STATUS_RESET,
                TransitionInterface::STATUS_UPGRADED,
                TransitionInterface::STATUS_CONFIGURED,
                TransitionInterface::STATUS_UNINSTALLED,
                TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED,
                TransitionInterface::STATUS_DISABLED__MOBILE_DISABLED,
            ],
            TransitionInterface::STATUS_DISABLED__MOBILE_ENABLED => [
                TransitionInterface::STATUS_RESET,
                TransitionInterface::STATUS_UPGRADED,
                TransitionInterface::STATUS_CONFIGURED,
                TransitionInterface::STATUS_UNINSTALLED,
                TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED,
                TransitionInterface::STATUS_DISABLED__MOBILE_DISABLED,
            ],
            TransitionInterface::STATUS_DISABLED__MOBILE_DISABLED => [
                TransitionInterface::STATUS_RESET,
                TransitionInterface::STATUS_UPGRADED,
                TransitionInterface::STATUS_CONFIGURED,
                TransitionInterface::STATUS_UNINSTALLED,
                TransitionInterface::STATUS_DISABLED__MOBILE_ENABLED,
                TransitionInterface::STATUS_ENABLED__MOBILE_DISABLED
            ],
            TransitionInterface::STATUS_UNINSTALLED => [
                TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED,
            ],
        ];

        $transitions = [];
        foreach ($allowedTransitions as $originStatus => $targetStatuses) {
            foreach ($targetStatuses as $targetStatus) {
                $transitions[] = new Transition($originStatus, $targetStatus);
            }
        }

        return $transitions;
    }

    private function isTransitionAllowed(string $transitionName, array $transitions): ?TransitionInterface
    {
        /**
         * @var TransitionInterface $transition
         */
        foreach ($transitions as $transition) {
            if (0 === strcasecmp($transitionName, $transition->getTransitionName())) {
                return $transition;
            }
        }

        return null;
    }
}
