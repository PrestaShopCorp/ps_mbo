<?php

use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Controller\AbstractAdminApiController;
use PrestaShop\Module\Mbo\Api\Exception\QueryParamsException;
use PrestaShop\Module\Mbo\Module\Command\ModuleStatusTransitionCommand;

class apiPsMboController extends AbstractAdminApiController
{
    public $type = Config::MODULE_ACTIONS;

    public function init(): void
    {
        parent::init();
        $this->setAllowAnonymous(true);
    }

    /**
     * @return void
     */
    public function postProcess()
    {
        try {
            $transition = Tools::getValue('action');
            $module = Tools::getValue('module');

            if (empty($transition) || empty($module)) {
                throw new QueryParamsException('You need transition and module parameters');
            }
            $command = new ModuleStatusTransitionCommand($transition, $module);
            $this->module->get('mbo.handlers.command.module_status_transition_handler')->handle($command);
        } catch (\Exception $exception) {
            $this->exitWithExceptionMessage($exception);
        }

        $this->exitWithResponse([
            'message' => $this->trans('Transition successfully executed'),
        ]);
    }
}
