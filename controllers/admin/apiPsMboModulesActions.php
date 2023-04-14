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
use PrestaShop\Module\Mbo\Distribution\Client;
use PrestaShop\Module\Mbo\Module\Action\ActionBuilder;
use PrestaShop\Module\Mbo\Module\Action\ActionInterface;
use PrestaShop\Module\Mbo\Module\Action\Scheduler;

/**
 * This controller is responsible to execute actions on modules installed on the current shop.
 * Caller have to be fully authenticated to perform actions given.
 */
class apiPsMboModulesActionsController extends AbstractAdminApiController
{
    /**
     * @return void
     */
    public function postProcess()
    {
        $response = null;
        try {
            $moduleAction = Tools::getValue('action');
            $moduleName = Tools::getValue('module');
            $moduleActionsRequest = Tools::getValue('request');
            $actionToExecute = null;

            if (empty($moduleActionsRequest)) {
                throw new QueryParamsException('[request] parameter is required');
            }

            if (!in_array($moduleActionsRequest, ['submit', 'process'])) {
                throw new QueryParamsException('[request] parameter must be submit or process');
            }

            // Buffer all upcoming output...
            ob_start();

            if ('submit' === $moduleActionsRequest) {
                // @TODO utiliser l'uuid de la requete
                // Et lancer l'action après l'avoir submit
                echo $this->module->get('mbo.modules.actions.scheduler')->receiveAction([
                    'action' => $moduleAction,
                    'module_name' => $moduleName,
                ]);
            } elseif ('process' === $moduleActionsRequest) {
                $processResult = $this->module->get('mbo.modules.actions.scheduler')->processNextAction();

                if ($processResult instanceof ActionInterface) {
                    $actionToExecute = $processResult;

                    // @TODO Renvoyer l'uuid et le shop_uuid et le nom du module
                    echo Scheduler::ACTION_STARTED;
                } else {
                    echo $processResult;
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

            // Execute action processing
            if ($actionToExecute instanceof ActionInterface) {
                // Notify Distribution API that action is being process
                /** @var Client $distributionApi */
                $distributionApi = $this->get('mbo.cdc.client.distribution_api');
                $distributionApi->setBearer($this->module->getAdminAuthenticationProvider()->getMboJWT());

                $distributionApi->notifyModuleAction($actionToExecute);

                if ($this->module->get('mbo.modules.actions.scheduler')->processAction($actionToExecute)) {
                    // Notify Distribution API that action have been processed
                    $distributionApi->notifyModuleAction($actionToExecute);
                }
            }
        } catch (\Exception $exception) {
            $this->exitWithExceptionMessage($exception);
        }

        $this->exitWithResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildSignatureMessage(): string
    {
        // Payload elements
        $request = Tools::getValue('request', '');
        $adminToken = Tools::getValue('admin_token', '');
        $actionUuid = Tools::getValue('action_uuid');

        if (
            !$request ||
            !$adminToken ||
            !$actionUuid
        ) {
            throw new IncompleteSignatureParamsException('Expected signature elements are not given');
        }

        $keyVersion = Tools::getValue('version');

        return json_encode([
            'request' => $request,
            'admin_token' => $adminToken,
            'action_uuid' => $actionUuid,
            'version' => $keyVersion,
        ]);
    }
}
