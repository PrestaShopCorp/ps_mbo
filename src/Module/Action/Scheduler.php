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

namespace PrestaShop\Module\Mbo\Module\Action;

class Scheduler
{
    const NO_ACTION_IN_QUEUE = 'NO_ACTION_IN_QUEUE';
    const ACTION_ALREADY_PROCESSING = 'ACTION_ALREADY_PROCESSING';
    const ACTION_STARTED = 'ACTION_STARTED';
    const ACTION_PROCESSED = 'ACTION_PROCESSED';
    const ACTION_SAVED = 'ACTION_SAVED';

    /**
     * @var ActionRetriever
     */
    private $actionRetriever;

    public function __construct(ActionRetriever $actionRetriever)
    {
        $this->actionRetriever = $actionRetriever;
    }

    /**
     * When the scheduler receives an action
     * It save it into the DB with a pending status and then returns the action UUID
     *
     * @return string
     */
    public function receiveAction(array $actionData): string
    {
        return $this->actionRetriever->saveAction($actionData);
    }

    /**
     * - If there is no action in progress and no pending action, Action process starts
     * - If there is an In progress action or other
     *
     * @return string|ActionInterface
     * @throws \Exception
     */
    public function processNextAction()
    {
        $nextAction = $this->getNextActionInQueue();

        if (null === $nextAction) {
            return self::NO_ACTION_IN_QUEUE;
        }

        if ($nextAction->isInProgress()) {
            return self::ACTION_ALREADY_PROCESSING;
        }

        return $this->actionRetriever->markActionAsProcessing($nextAction);
    }

    public function getNextActionInQueue(): ?ActionInterface
    {
        $actionsInDb = $this->actionRetriever->getActionsInDb();

        if (empty($actionsInDb)) {
            return null;
        }

        // Here the actions are in status PENDING or PROCESSING ordered by date_add.
        // We check if there is an PROCESSING action.
        // If found, we return it, otherwise we return the first one in the array
        foreach ($actionsInDb as $action) {
            if ($action->isInProgress()) {
                return $action;
            }
        }

        return $actionsInDb[0];
    }

    public function processAction(ActionInterface $action): bool
    {
        try {
            $action->execute();
        } catch(\Exception $e) {
            return false;
        }

        $this->actionRetriever->markActionAsProcessed($action);

        return true;
    }
}
