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

namespace PrestaShop\Module\Mbo\Api\Controller;

use Configuration;
use Exception;
use ModuleAdminController;
use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Exception\IncompleteSignatureParamsException;
use PrestaShop\Module\Mbo\Api\Exception\QueryParamsException;
use PrestaShop\Module\Mbo\Api\Exception\RetrieveNewKeyException;
use PrestaShop\Module\Mbo\Api\Exception\UnauthorizedException;
use PrestaShop\Module\Mbo\Api\Handler\ErrorHandler\ErrorHandler;
use PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider;
use PrestaShop\Module\Mbo\Api\Security\AuthorizationChecker;
use ps_mbo;
use Tools;

abstract class AbstractAdminApiController extends ModuleAdminController
{
    /**
     * Endpoint name
     *
     * @var string
     */
    public $type = '';

    /**
     * @var AdminAuthenticationProvider
     */
    protected $adminAuthenticationProvider;

    /**
     * @var ps_mbo
     */
    public $module;

    /**
     * @var ErrorHandler
     */
    public $errorHandler;

    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

    public function __construct()
    {
        parent::__construct();

        $this->errorHandler = $this->module->get(ErrorHandler::class);
        $this->adminAuthenticationProvider = $this->module->get(AdminAuthenticationProvider::class);
        $this->authorizationChecker = $this->module->get(AuthorizationChecker::class);
    }

    public function init(): void
    {
        try {
            $this->authorize();
        } catch (IncompleteSignatureParamsException $exception) {
            $this->errorHandler->handle($exception);
            $this->exitWithExceptionMessage($exception);
        } catch (UnauthorizedException $exception) {
            $this->errorHandler->handle($exception);
            $this->exitWithExceptionMessage($exception);
        } catch (RetrieveNewKeyException $exception) {
            $this->errorHandler->handle($exception);
            $this->exitWithExceptionMessage($exception);
        }

        parent::init();

        $this->adminAuthenticationProvider->extendTokenValidity();
    }

    protected function exitWithResponse(array $response): void
    {
        $httpCode = isset($response['httpCode']) ? (int) $response['httpCode'] : 200;

        $shopUuid = Configuration::get('PS_MBO_SHOP_ADMIN_UUID');

        $response['shop_uuid'] = $shopUuid;

        $this->dieWithResponse($response, $httpCode);
    }

    protected function exitWithExceptionMessage(Exception $exception): void
    {
        $code = $exception->getCode() == 0 ? 500 : $exception->getCode();

        if ($exception instanceof QueryParamsException) {
            $code = Config::INVALID_URL_QUERY;
        } elseif ($exception instanceof IncompleteSignatureParamsException) {
            $code = Config::INCOMPLETE_SIGNATURE_ERROR_CODE;
        } elseif ($exception instanceof UnauthorizedException) {
            $code = Config::UNAUTHORIZED_ERROR_CODE;
        } elseif ($exception instanceof RetrieveNewKeyException) {
            $code = Config::RETRIEVE_NEW_KEY_ERROR_CODE;
        }

        $response = [
            'object_type' => $this->type,
            'status' => false,
            'httpCode' => $code,
            'message' => $exception->getMessage(),
        ];

        $this->dieWithResponse($response, (int) $code);
    }

    private function dieWithResponse(array $response, int $code): void
    {
        $httpStatusText = "HTTP/1.1 $code";

        if (array_key_exists($code, Config::HTTP_STATUS_MESSAGES)) {
            $httpStatusText .= ' ' . Config::HTTP_STATUS_MESSAGES[$code];
        } elseif (isset($response['body']['statusText'])) {
            $httpStatusText .= ' ' . $response['body']['statusText'];
        }

        $response['httpCode'] = $code;

        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/json;charset=utf-8');
        header($httpStatusText);

        echo json_encode($response, JSON_UNESCAPED_SLASHES);

        exit;
    }

    /**
     * @throws IncompleteSignatureParamsException
     * @throws RetrieveNewKeyException
     * @throws UnauthorizedException
     */
    private function authorize()
    {
        $keyVersion = \Tools::getValue('version');
        $signature = isset($_SERVER['HTTP_MBO_SIGNATURE']) ? $_SERVER['HTTP_MBO_SIGNATURE'] : false;

        // Payload elements
        $action = Tools::getValue('action');
        $module = Tools::getValue('module');
        $userId = Tools::getValue('user_id', null);
        $adminToken = Tools::getValue('admin_token');
        $actionUuid = Tools::getValue('action_uuid');

        if (
            !$keyVersion ||
            !$signature ||
            !$action ||
            !$module ||
            !$adminToken ||
            !$actionUuid
        ) {
            throw new IncompleteSignatureParamsException('Expected signature elements are not given');
        }

        $message = json_encode([
            'action' => $action,
            'module' => $module,
            'user_id' => $userId,
            'admin_token' => $adminToken,
            'action_uuid' => $actionUuid,
            'version' => $keyVersion,
        ]);

        $this->authorizationChecker->verify($keyVersion, $signature, $message);
    }
}
