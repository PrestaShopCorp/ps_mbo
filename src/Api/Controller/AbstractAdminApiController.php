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

use Exception;
use Google\ApiCore\ValidationException;
use ModuleAdminController;
use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Exception\EnvVarException;
use PrestaShop\Module\Mbo\Api\Exception\FirebaseException;
use PrestaShop\Module\Mbo\Api\Exception\QueryParamsException;
use PrestaShop\Module\Mbo\Api\Exception\UnauthorizedException;
use PrestaShop\Module\Mbo\Api\Handler\ErrorHandler\ErrorHandler;
use PrestaShop\Module\Mbo\Api\Service\ApiAuthorizationService;
use PrestaShopDatabaseException;
use ps_mbo;

abstract class AbstractAdminApiController extends ModuleAdminController
{
    /**
     * Endpoint name
     *
     * @var string
     */
    public $type = '';

    /**
     * Timestamp when script started
     *
     * @var int
     */
    public $startTime;

    /**
     * @var ApiAuthorizationService
     */
    protected $authorizationService;

    /**
     * @var ps_mbo
     */
    public $module;

    /**
     * @var ErrorHandler
     */
    public $errorHandler;

    public function __construct()
    {
        parent::__construct();

//        $this->controller_type = 'module';

        $this->errorHandler = $this->module->get(ErrorHandler::class);
        $this->authorizationService = $this->module->get(ApiAuthorizationService::class);
    }

    public function init(): void
    {
        parent::init();

        $this->startTime = time();

        try {
            $this->authorize();
        } catch (UnauthorizedException $exception) {
            $this->errorHandler->handle($exception);
            $this->exitWithExceptionMessage($exception);
        } catch (PrestaShopDatabaseException $exception) {
            $this->errorHandler->handle($exception);
            $this->exitWithExceptionMessage($exception);
        } catch (EnvVarException $exception) {
            $this->errorHandler->handle($exception);
            $this->exitWithExceptionMessage($exception);
        } catch (ValidationException $exception) {
            $this->errorHandler->handle($exception);
            $this->exitWithExceptionMessage($exception);
        }
    }

    /**
     * @throws PrestaShopDatabaseException|EnvVarException|UnauthorizedException|ValidationException
     */
    private function authorize(): void
    {
        $authorizationResponse = $this->authorizationService->authorizeCall();
        if (!$authorizationResponse) {
            throw new UnauthorizedException('Not Authorized');
        }
    }

    protected function exitWithResponse(array $response): void
    {
        $httpCode = isset($response['httpCode']) ? (int) $response['httpCode'] : 200;

        $this->dieWithResponse($response, $httpCode);
    }

    protected function exitWithExceptionMessage(Exception $exception): void
    {
        $code = $exception->getCode() == 0 ? 500 : $exception->getCode();

        if ($exception instanceof PrestaShopDatabaseException) {
            $code = Config::DATABASE_QUERY_ERROR_CODE;
        } elseif ($exception instanceof EnvVarException) {
            $code = Config::ENV_MISCONFIGURED_ERROR_CODE;
        } elseif ($exception instanceof FirebaseException) {
            $code = Config::REFRESH_TOKEN_ERROR_CODE;
        } elseif ($exception instanceof QueryParamsException) {
            $code = Config::INVALID_URL_QUERY;
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

        if (array_key_exists((int) $code, Config::HTTP_STATUS_MESSAGES)) {
            $httpStatusText .= ' ' . Config::HTTP_STATUS_MESSAGES[(int) $code];
        } elseif (isset($response['body']['statusText'])) {
            $httpStatusText .= ' ' . $response['body']['statusText'];
        }

        $response['httpCode'] = (int) $code;

        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/json;charset=utf-8');
        header($httpStatusText);

        echo json_encode($response, JSON_UNESCAPED_SLASHES);

        exit;
    }
}
