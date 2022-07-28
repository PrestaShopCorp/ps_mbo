<?php

use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Controller\AbstractAdminApiController;
use PrestaShop\Module\Mbo\Api\Exception\QueryParamsException;
use PrestaShop\Module\Mbo\Module\Command\ModuleStatusTransitionCommand;

class apiPsMboController extends AbstractAdminApiController
{
    public $type = Config::MODULE_ACTIONS;

    /**
     * @return void
     */
    public function postProcess()
    {
        try {
            $transition = Tools::getValue('action');
            $moduleName = Tools::getValue('module');
            $source = Tools::getValue('source', null);

            if (empty($transition) || empty($moduleName)) {
                throw new QueryParamsException('You need transition and module parameters');
            }
            $command = new ModuleStatusTransitionCommand($transition, $moduleName, $source);
            /**
             * @var \PrestaShop\Module\Mbo\Module\Module $module
             */
            $module = $this->module->get('mbo.modules.state_machine.module_status_transition_handler')->handle($command);
        } catch (\Exception $exception) {
            $this->exitWithExceptionMessage($exception);
        }

        $moduleUrls = $module->get('urls');

        $this->exitWithResponse([
            'message' => $this->trans('Transition successfully executed'),
            'module_status' => $module->getStatus(),
            'version' => $module->get('version'),
            'config_url' => (bool) $module->get('is_configurable') && isset($moduleUrls['configure']) ? $moduleUrls['configure'] : null,
        ]);
    }
}
