<?php

use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Controller\AbstractAdminApiController;
use PrestaShop\Module\Mbo\Api\Exception\IncompleteSignatureParamsException;
use PrestaShop\Module\Mbo\Api\Exception\QueryParamsException;
use PrestaShop\Module\Mbo\Api\Exception\RetrieveNewKeyException;
use PrestaShop\Module\Mbo\Api\Exception\UnauthorizedException;
use PrestaShop\Module\Mbo\Distribution\Config\Command\ConfigChangeCommand;

/**
 * This controller is responsible to execute actions on modules installed on the current shop.
 * Caller have to be fully authenticated to perform actions given.
 */
class apiConfigPsMboController extends AbstractAdminApiController
{
    public $type = Config::MODULE_ACTIONS;

    /**
     * @return void
     */
    public function postProcess()
    {
        try {
            $command = new ConfigChangeCommand(
                Tools::getValue('config'),
                _PS_VERSION_,
                $this->module->version
            );

            $configCollection = $this->module->get('mbo.distribution.api_config_change_handler')->handle($command);

        } catch (\Exception $exception) {
            $this->exitWithExceptionMessage($exception);
        }

        $this->exitWithResponse([
            'message' => 'Config successfully applied',
        ]);
    }
}
