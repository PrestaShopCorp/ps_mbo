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
use ModuleAdminController;
use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Exception\IncompleteSignatureParamsException;
use PrestaShop\Module\Mbo\Api\Exception\QueryParamsException;
use PrestaShop\Module\Mbo\Api\Exception\RetrieveNewKeyException;
use PrestaShop\Module\Mbo\Api\Exception\UnauthorizedException;
use PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider;
use PrestaShop\Module\Mbo\Api\Security\AuthorizationChecker;
use PrestaShop\Module\Mbo\Helpers\Config as ConfigHelper;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use ps_mbo;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tools;

abstract class AbstractAdminApiController extends FrameworkBundleAdminController
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
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct()
    {
        parent::__construct();

//        $this->adminAuthenticationProvider = $this->get('mbo.security.admin_authentication.provider');
//        $this->authorizationChecker = $this->get(AuthorizationChecker::class);
//        $this->logger = $this->get('logger');
//        $this->ajax = true;
//        $this->json = true;
//        $this->content_only = true;
//        $this->display_header = false;
//        $this->display_header_javascript = false;
    }

    public function init(): void
    {
        try {
            $this->get('logger')->info('API Call received = ' . $_SERVER['REQUEST_URI']);
//            $this->authorize();
        } catch (IncompleteSignatureParamsException $exception) {
            $this->exitWithExceptionMessage($exception);
        } catch (UnauthorizedException $exception) {
            $this->exitWithExceptionMessage($exception);
        } catch (RetrieveNewKeyException $exception) {
            $this->exitWithExceptionMessage($exception);
        }

        parent::init();
    }

    protected function exitWithResponse(array $response): JsonResponse
    {
        $httpCode = isset($response['httpCode']) ? (int) $response['httpCode'] : 200;

        $shopUuid = ConfigHelper::getShopMboUuid();

        $response['shop_uuid'] = $shopUuid;

        return $this->dieWithResponse($response, $httpCode);
    }

    protected function exitWithExceptionMessage(Exception $exception): JsonResponse
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

        return $this->dieWithResponse($response, (int) $code);
    }

    private function dieWithResponse(array $response, int $code): JsonResponse
    {
        $httpStatusText = "HTTP/1.1 $code";

        if (array_key_exists($code, Config::HTTP_STATUS_MESSAGES)) {
            $httpStatusText .= ' ' . Config::HTTP_STATUS_MESSAGES[$code];
        } elseif (isset($response['body']['statusText'])) {
            $httpStatusText .= ' ' . $response['body']['statusText'];
        }

//        $response['httpCode'] = $code;

//        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
//        header('Content-Type: application/json;charset=utf-8');
//        header($httpStatusText);

        return new JsonResponse($response, $code, [
            $httpStatusText,
            'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            'Content-Type: application/json;charset=utf-8',
        ]);
//
//        exit;
    }

    /**
     * @throws IncompleteSignatureParamsException
     * @throws RetrieveNewKeyException
     * @throws UnauthorizedException
     */
    protected function isGranted($attributes, $subject = null): bool
    {
//        $controller = new \AdminController('apiPsMbo');
//        if (!$controller->checkAccess()) {
//            $a = 1;
//        }
        $keyVersion = Tools::getValue('version');
        $signature = isset($_SERVER['HTTP_MBO_SIGNATURE']) ? $_SERVER['HTTP_MBO_SIGNATURE'] : false;

        if (!$keyVersion || !$signature) {
            throw new IncompleteSignatureParamsException('Expected signature elements are not given');
        }

        $message = $this->buildSignatureMessage();

        $this->get(AuthorizationChecker::class)->verify($keyVersion, $signature, $message);
    }

    /**
     * Generate elements composing the signature.
     * This is the standard composition.
     * Please build your own if other elements are included to the signature.
     *
     * @return string
     *
     * @throws IncompleteSignatureParamsException
     */
    protected function buildSignatureMessage(): string
    {
        // Payload elements
        $adminToken = Tools::getValue('admin_token');
        $actionUuid = Tools::getValue('action_uuid');

        if (
            !$adminToken ||
            !$actionUuid
        ) {
            throw new IncompleteSignatureParamsException('Expected signature elements are not given');
        }

        $keyVersion = Tools::getValue('version');

        return json_encode([
            'admin_token' => $adminToken,
            'action_uuid' => $actionUuid,
            'version' => $keyVersion,
        ]);
    }

    public function displayAjax()
    {
        (new JsonResponse())->send();
//        header('Content-Type: application/json');
//        $this->ajaxRender($this->content);
        // do nothing
    }
}
