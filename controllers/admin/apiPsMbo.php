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

use PrestaShop\Module\Mbo\Api\Controller\AbstractAdminApiController;
use PrestaShop\Module\Mbo\Api\Exception\IncompleteSignatureParamsException;
use PrestaShop\Module\Mbo\Api\Exception\QueryParamsException;
use PrestaShop\Module\Mbo\Api\Service\Factory as ExcutorsFactory;
use PrestaShop\Module\Mbo\Api\Service\ModuleActionExecutor;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\Module\Mbo\Module\Action\ActionInterface;
use PrestaShop\Module\Mbo\Module\Action\Scheduler;

/**
 * This controller is responsible to execute actions on modules installed on the current shop.
 * Caller have to be fully authenticated to perform actions given.
 */
class apiPsMboController extends AbstractAdminApiController
{
    /**
     * @return void
     */
    public function postProcess()
    {
        $response = null;
        try {
            $service = Tools::getValue('service');
            if (empty($service)) {
                throw new QueryParamsException('[service] parameter is required');
            }

            $actionUuid = Tools::getValue('action_uuid');
            $moduleName = Tools::getValue('module', '');

            $actionToExecute = null;

            if (ModuleActionExecutor::SERVICE === $service) { // Asynchronous request
                $moduleAction = Tools::getValue('action');
                $moduleActionsRequest = Tools::getValue('request');

                if (empty($moduleActionsRequest)) {
                    throw new QueryParamsException('[request] parameter is required');
                }

                if (!in_array($moduleActionsRequest, ['submit', 'process'])) {
                    throw new QueryParamsException('[request] parameter must be submit or process');
                }

                // Buffer all upcoming output...
                ob_start();

                if ('submit' === $moduleActionsRequest) {
                    // Save the received action
                    $savedActionUuid = $this->module->get('mbo.modules.actions.scheduler')->receiveAction([
                        'action' => $moduleAction,
                        'action_uuid' => $actionUuid,
                        'module_name' => $moduleName,
                    ]);

                    // Execute the next action in the queue if there is noone in progress
                    $processResult = $this->module->get('mbo.modules.actions.scheduler')->processNextAction();
                    if ($processResult instanceof ActionInterface) {
                        $actionToExecute = $processResult;
                    }

                    echo json_encode([
                        'message' => Scheduler::ACTION_SAVED,
                        'action_uuid' => $savedActionUuid,
                        'shop_uuid' => Config::getShopMboUuid(),
                        'module' => $moduleName,
                        'action' => $moduleAction,
                    ]);

                } elseif ('process' === $moduleActionsRequest) {
                    $processResult = $this->module->get('mbo.modules.actions.scheduler')->processNextAction();

                    if ($processResult instanceof ActionInterface) {
                        $actionToExecute = $processResult;

                        echo json_encode([
                            'message' => Scheduler::ACTION_STARTED,
                            'action_uuid' => $processResult->getActionUuid(),
                            'shop_uuid' => Config::getShopMboUuid(),
                            'module' => $moduleName,
                        ]);
                    } else {
                        echo json_encode([
                            'message' => $processResult,
                            'shop_uuid' => Config::getShopMboUuid(),
                        ]);
                    }
                }

                // Get the size of the output.
                $size = ob_get_length();

                // Disable compression (in case content length is compressed).
                header("Content-Encoding: none");

                // Set the content length of the response.
                header("Content-Length: {$size}");

                // Close the connection.
                header("Connection: close");

                // Flush all output.
                ob_end_flush();
                @ob_flush();
                flush();
            }

            /** @var ExcutorsFactory $executorsFactory */
            $executorsFactory = $this->module->get('mbo.api.service.factory');

            $response = $executorsFactory->build($service)->execute($this->module, $actionToExecute);
        } catch (\Exception $exception) {
            $this->exitWithExceptionMessage($exception);
        }

        if (null !== $response) {
            $this->exitWithResponse($response);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function buildSignatureMessage(): string
    {
        // Payload elements
        $action = Tools::getValue('action', '');
        $module = Tools::getValue('module', '');
        $adminToken = Tools::getValue('admin_token', '');
        $actionUuid = Tools::getValue('action_uuid');

        if (
            !$action ||
            !$module ||
            !$adminToken ||
            !$actionUuid
        ) {
            throw new IncompleteSignatureParamsException('Expected signature elements are not given');
        }

        $keyVersion = Tools::getValue('version');

        return json_encode([
            'action' => $action,
            'module' => $module,
            'admin_token' => $adminToken,
            'action_uuid' => $actionUuid,
            'version' => $keyVersion,
        ]);
    }
}
