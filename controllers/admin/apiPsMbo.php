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
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;

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
            /** @var ExcutorsFactory $executorsFactory */
            $executorsFactory = $this->module->get('mbo.api.service.factory');

            $response = $executorsFactory->build($service)->execute($this->module);
        } catch (\Exception $exception) {
            ErrorHelper::reportError($exception);
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
