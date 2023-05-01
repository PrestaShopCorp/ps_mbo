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

use Db;

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

    /**
     * @param array $actionData
     *
     * @return string
     *
     * @throws \Exception
     */
    public function saveAction(array $actionData): string
    {
        $action = $this->actionBuilder->build($actionData);

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'mbo_action_queue`(`action_uuid`,`action`,`module`,`parameters`,`status`,`date_add`) VALUES ';
        $sql .= sprintf(
            "('%s', '%s', '%s', %s, '%s', NOW());",
            $action->getActionUuid(),
            $action->getActionName(),
            $action->getModuleName(),
            !empty($action->getParameters()) ? "'" . json_encode($action->getParameters()) . "'" : 'NULL',
            $action->getStatus()
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
        if (count($this->db->executeS('SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'mbo_action_queue\' '))) { //check if table exist
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
                    $parameters = json_decode($action['parameters'], true);

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
            "UPDATE `%smbo_action_queue` SET `status` = '%s', `date_started` = NOW() WHERE `action_uuid` = '%s';",
            _DB_PREFIX_,
            ActionInterface::PROCESSING,
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
            "UPDATE `%smbo_action_queue` SET `status` = '%s', `date_ended` = NOW() WHERE `action_uuid` = '%s';",
            _DB_PREFIX_,
            ActionInterface::PROCESSED,
            $action->getActionUuid()
        );

        if (false === $this->db->execute($sql)) {
            throw new \Exception('Unable to update action');
        }

        $action->setStatus(ActionInterface::PROCESSED);

        return $action;
    }

    public function getProcessingAction(): ?ActionInterface
    {
        if (count($this->db->executeS('SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'mbo_action_queue\' '))) { //check if table exist
            $query = "SELECT
               `action_uuid`,
               `action`,
               `module`,
               `parameters`,
               `status`,
               `date_add`
            FROM " . _DB_PREFIX_ . "mbo_action_queue
            WHERE `status` = '" . ActionInterface::PROCESSING . "'
            ORDER BY `date_add` ASC";

            /** @var array $action */
            $action = $this->db->getRow($query, false);

            if (!is_array($action)) {
                return null;
            }

            $source = null;
            if (null !== $action['parameters']) {
                $parameters = json_decode($action['parameters'], true);

                if (!empty($parameters['source'])) {
                    $source = $parameters['source'];
                }
            }

            return $this->actionBuilder->build([
                'action' => $action['action'],
                'action_uuid' => $action['action_uuid'],
                'module_name' => $action['module'],
                'source' => $source,
                'status' => $action['status'],
            ]);
        }

        return null;
    }
}
