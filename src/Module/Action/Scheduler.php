<?php

namespace PrestaShop\Module\Mbo\Module\Action;

class Scheduler
{
    const NO_ACTION_IN_QUEUE = 'NO_ACTION_IN_QUEUE';
    const ACTION_ALREADY_PROCESSING = 'ACTION_ALREADY_PROCESSING';
    const ACTION_STARTED = 'ACTION_STARTED';
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
     * It save it into the DB with a pending status and then aswers OK
     *
     * @return bool
     */
    public function receiveAction(array $actionData): bool
    {
        return $this->actionRetriever->saveAction($actionData);
    }

    /**
     * - If there is no action in progress and no pending action, Action process starts
     * - If there is an In progress action or other
     *
     * @return string
     */
    public function processNextAction(): string
    {
        $nextAction = $this->getNextActionInQueue();

        if (null === $nextAction) {
            return self::NO_ACTION_IN_QUEUE;
        }

        if ($nextAction->isInProgress()) {
            return self::ACTION_ALREADY_PROCESSING;
        }

        if ($this->processAction($nextAction)) {
            return self::ACTION_STARTED . ' ' . (new \DateTime())->format('d/m/Y H:i:s');
        }

        throw new \Exception('There is an issue when processing next action in queue');
    }

    public function getNextActionInQueue(): ?ActionInterface
    {
        $actionsInDb = $this->actionRetriever->getActionsInDb();

        if (empty($actionsInDb)) {
            return null;
        }

        usort($actionsInDb, function(ActionInterface $action) {
            return $action->isInProgress() ? -1 : 1;
        });

        return $actionsInDb[0];
    }

    private function processAction(ActionInterface $action): bool
    {
        // @TODO change status, execute, ...
        return true;
    }
}
