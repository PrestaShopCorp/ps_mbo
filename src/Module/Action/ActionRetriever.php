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
            Uuid::uuid4()->toString(),
            $action->getActionName(),
            $action->getModuleName(),
            !empty($action->getParameters()) ? "'" . json_encode($action->getParameters()) . "'" : 'NULL',
            $action->getStatus(),
            $dateAdd
        );

        return $this->db->execute($sql);
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
            WHERE `status` <> 'PROCESSED'";

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
                    'module_name' => $action['module'],
                    'source' => $source,
                ]);
            }

            return $actions;
        }

        return [];
    }

}
