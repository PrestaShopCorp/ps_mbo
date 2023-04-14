<?php

namespace PrestaShop\Module\Mbo\Module\Action;

class Scheduler
{
    const NO_ACTION_IN_QUEUE = 'NO_ACTION_IN_QUEUE';
    const ACTION_ALREADY_PROCESSING = 'ACTION_ALREADY_PROCESSING';
    const ACTION_STARTED = 'ACTION_STARTED';
    const ACTION_PROCESSED = 'ACTION_PROCESSED';
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
            $f = fopen('/home/isow/workspace/PrestaShop/8.1/toto.log', "a+");
            fwrite($f, $e->getMessage() . "\n");
            fclose($f);
        }

        $f = fopen('/home/isow/workspace/PrestaShop/8.1/toto.log', "a+");
        fwrite($f, (new \DateTime())->format('d/m/Y H:i:s') . "\n");
        fclose($f);

        $this->actionRetriever->markActionAsProcessed($action);

        return true;
    }
}
