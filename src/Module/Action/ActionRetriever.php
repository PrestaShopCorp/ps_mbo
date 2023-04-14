<?php

namespace PrestaShop\Module\Mbo\Module\Action;

use Db;
use Ramsey\Uuid\Uuid;

class ActionRetriever
{
    /**
     * @var ActionBuilder
     */
    private $actionBuilder;

    public function __construct(ActionBuilder $actionBuilder)
    {
        $this->db = Db::getInstance();
        $this->actionBuilder = $actionBuilder;
    }

    public function saveAction(array $actionData)
    {
        $action = $this->actionBuilder->build($actionData);

        $dateAdd = (new \DateTime())->format('Y-m-d H:i:s');
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'mbo_action_queue`(`action_uuid`,`action`,`module`,`parameters`,`status`,`date_add`) VALUES ';
        $sql .= sprintf(
            "('%s', '%s', '%s', %s, '%s', '%s');",
            $action->getActionUuid(),
            $action->getActionName(),
            $action->getModuleName(),
            !empty($action->getParameters()) ? "'" . json_encode($action->getParameters()) . "'" : 'NULL',
            $action->getStatus(),
            $dateAdd
        );

        if (false === $this->db->execute($sql)) {
            throw new \Exception('Unable to create action');
        }

        return $action->getActionUuid();
    }
    /**
     * @return ActionInterface[]
     */
    public function getActionsInDb(): array
    {
        if (count(Db::getInstance()->executeS('SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'mbo_action_queue\' '))) { //check if table exist
            $query = "SELECT
               `action_uuid`,
               `action`,
               `module`,
               `parameters`,
               `status`,
               `date_add`
            FROM " . _DB_PREFIX_ . "mbo_action_queue
            WHERE `status` <> '" . ActionInterface::PROCESSED . "'
            ORDER BY `date_add` ASC";

            /** @var array $results */
            $results = $this->db->executeS($query);

            if (!is_array($results)) {
                throw new PrestaShopDatabaseException(sprintf('Retrieving actions queue from DB returns a non array : %s. Query was : %s', gettype($results), $query));
            }

            $actions = [];
            foreach($results as $action) {
                $source = null;
                if (null !== $action['parameters']) {
                    $aprameters = json_decode($action['parameters'], true);

                    if (!empty($parameters['source'])) {
                        $source = $parameters['source'];
                    }
                }

                $actions[] = $this->actionBuilder->build([
                    'action' => $action['action'],
                    'action_uuid' => $action['action_uuid'],
                    'module_name' => $action['module'],
                    'source' => $source,
                    'status' => $action['status'],
                ]);
            }

            return $actions;
        }

        return [];
    }

    public function markActionAsProcessing(ActionInterface $action): ActionInterface
    {
        $sql = sprintf(
            "UPDATE `%smbo_action_queue` SET `status` = '%s', `date_started` = '%s' WHERE `action_uuid` = '%s';",
            _DB_PREFIX_,
            ActionInterface::PROCESSING,
            (new \DateTime())->format('Y-m-d H:i:s'),
            $action->getActionUuid()
        );

        if (false === $this->db->execute($sql)) {
            throw new \Exception('Unable to update action');
        }

        $action->setStatus(ActionInterface::PROCESSING);

        return $action;
    }

    public function markActionAsProcessed(ActionInterface $action): ActionInterface
    {
        $sql = sprintf(
            "UPDATE `%smbo_action_queue` SET `status` = '%s', `date_ended` = '%s' WHERE `action_uuid` = '%s';",
            _DB_PREFIX_,
            ActionInterface::PROCESSED,
            (new \DateTime())->format('Y-m-d H:i:s'),
            $action->getActionUuid()
        );

        if (false === $this->db->execute($sql)) {
            throw new \Exception('Unable to update action');
        }

        $action->setStatus(ActionInterface::PROCESSED);

        return $action;
    }

}
