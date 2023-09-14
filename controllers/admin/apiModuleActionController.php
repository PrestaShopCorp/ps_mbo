<?php
namespace PrestaShop\Module\Mbo\PublicApi;

use PrestaShop\Module\Mbo\Api\Controller\AbstractAdminApiController;
use PrestaShop\Module\Mbo\Api\Exception\QueryParamsException;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\Module\Mbo\Module\Action\ActionInterface;
use PrestaShop\Module\Mbo\Module\Action\Scheduler;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Tools;

// @TODO : How about the PS security and adminTokens ? ping @vincent
class apiModuleActionController extends AbstractAdminApiController
{
    /**
     * @AdminSecurity("is_granted('read', request.get('apiPsMbo'))")
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted([]);

        $actionUuid = Tools::getValue('action_uuid');
        $moduleName = Tools::getValue('module', '');
        $moduleAction = Tools::getValue('action');
        $moduleActionsRequest = Tools::getValue('request');

        if (empty($moduleActionsRequest)) {
            throw new QueryParamsException('[request] parameter is required');
        }

        if (!in_array($moduleActionsRequest, ['submit', 'process'])) {
            throw new QueryParamsException('[request] parameter must be submit or process');
        }

        // Submit a module action
        if ('submit' === $moduleActionsRequest) {
            $response = $this->submitModuleAction($moduleAction, $actionUuid, $moduleName);
        }

        // Process a module action
        if ('process' === $moduleActionsRequest) {
            $response = $this->processNextModuleAction();
        }

        return new JsonResponse($response);
    }

    private function submitModuleAction(string $moduleAction, string $actionUuid, string $moduleName)
    {
        /** @var Scheduler $actionsScheduler */
        $actionsScheduler = $this->get('mbo.modules.actions.scheduler');

        // Save the received action
        $savedActionUuid = $actionsScheduler->receiveAction([
            'action' => $moduleAction,
            'action_uuid' => $actionUuid,
            'module_name' => $moduleName,
        ]);

        // Execute the next action in the queue if there is noone in progress
        $processResult = $actionsScheduler->processNextAction();
        if ($processResult instanceof ActionInterface) {
            $actionToExecute = $processResult;
        }

        return [
            'message' => Scheduler::ACTION_SAVED,
            'action_uuid' => $savedActionUuid,
            'shop_uuid' => Config::getShopMboUuid(),
            'module' => $moduleName,
            'action' => $moduleAction,
        ];
    }

    private function processNextModuleAction(): array
    {
        /** @var Scheduler $actionsScheduler */
        $actionsScheduler = $this->get('mbo.modules.actions.scheduler');

        $processResult = $actionsScheduler->processNextAction();

        if ($processResult instanceof ActionInterface) {
            return [
                'message' => Scheduler::ACTION_STARTED,
                'action_uuid' => $processResult->getActionUuid(),
                'shop_uuid' => Config::getShopMboUuid(),
                'module' => $processResult->getModuleName(),
            ];
        } else {
            return [
                'message' => $processResult,
                'shop_uuid' => Config::getShopMboUuid(),
            ];
        }
    }
}
