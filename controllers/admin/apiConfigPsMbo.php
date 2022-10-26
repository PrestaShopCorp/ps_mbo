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
use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Controller\AbstractAdminApiController;
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
