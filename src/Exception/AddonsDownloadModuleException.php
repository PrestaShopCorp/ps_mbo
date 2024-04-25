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

namespace PrestaShop\Module\Mbo\Exception;

use Exception;
use GuzzleHttp\Exception\ClientException;

class AddonsDownloadModuleException extends Exception
{
    private const UNKNOWN_ADDONS_CODE = '0030';

    const WRONG_PARAMETERS = '0000';
    const WRONG_MODULE_KEY = '0001';
    const UNKNOWN_MODULE = '0002';
    const INVALID_CREDENTIALS = '0003';
    const INVALID_EMAIL_OR_PASSWORD = '0004';
    const ACCESS_DENIED = '0005';
    const INVALID_PRODUCT_FILE = '0006';
    const TM_CURL_INVALID_LINK = '0007';
    const TM_INACTIVE_LINK = '0008';
    const TM_INVALID_ORDER = '0009';
    const INVALID_METHOD = '0010';
    const HTTPS_REQUIRED = '0011';
    const INVALID_AUTH = '0012';
    const INVALID_EMAIL = '0013';
    const INVALID_PARAMETERS = '0014';
    const METHOD_UNDEFINED = '0015';
    const KO_LABEL = '0016';
    const SERVICE_UNAVAILABLE = '0017';
    const NO_ZIP_SERVICE = '0018';
    const WEBSITE_REFUSED = '0019';
    const NO_SELECTABLE_BUSINESS_CARE = '0020';

    public static $errors = [
        self::WRONG_PARAMETERS => 'Wrong Parameters',
        self::WRONG_MODULE_KEY => 'Wrong module key',
        self::UNKNOWN_MODULE => 'Unknown module',
        self::INVALID_CREDENTIALS => 'Invalid credentials',
        self::INVALID_EMAIL_OR_PASSWORD => 'Invalid Email or Password too short',
        self::ACCESS_DENIED => 'Access Denied',
        self::INVALID_PRODUCT_FILE => 'Invalid product file',
        self::TM_CURL_INVALID_LINK => 'Fatal error : CURL on TM link not valid.',
        self::TM_INACTIVE_LINK => 'Fatal error : TM link is inactive.',
        self::TM_INVALID_ORDER => 'Fatal error : Invalid TM order.',
        self::INVALID_METHOD => 'Method doesn\'t exist !',
        self::HTTPS_REQUIRED => 'HTTPS Required !',
        self::INVALID_AUTH => 'Invalid Authentification.',
        self::INVALID_EMAIL => 'Fatal error : Invalid Email.',
        self::INVALID_PARAMETERS => 'Invalid Parameters',
        self::METHOD_UNDEFINED => 'Fatal error : method is undefined.',
        self::KO_LABEL => 'ko',
        self::SERVICE_UNAVAILABLE => 'Service unavailable',
        self::NO_ZIP_SERVICE => 'No download, Service',
        self::WEBSITE_REFUSED => 'Store URL not matching',
        self::NO_SELECTABLE_BUSINESS_CARE => 'No selectable support subscription',
    ];

    /**
     * @var array
     */
    private $context;

    /**
     * @var string
     */
    private $technicalErrorMessage;

    public function __construct(ClientException $previous, array $context = [])
    {
        $addonsError = $this->getErrorSentByAddons($previous);
        parent::__construct(
            $this->getCustomizedMessage($addonsError),
            $addonsError['http_code'] ?? 0,
            $previous
        );

        $context['previous_exception_class'] = get_class($previous);
        $context['previous_exception_message'] = $previous->getMessage();

        $this->context = $context;
        $this->technicalErrorMessage = $addonsError['technical_error_message'];
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getTechnicalErrorMessage(): string
    {
        return $this->technicalErrorMessage;
    }

    private function getErrorSentByAddons(ClientException $exception): array
    {
        $rawContent = $exception->getResponse()->getBody()->getContents();
        $jsonContent = json_decode($rawContent, true);

        $code = self::UNKNOWN_ADDONS_CODE;
        if (is_array($jsonContent) && isset($jsonContent['errors']['code'])) {
            $code = $jsonContent['errors']['code'];
        }

        $message = self::$errors[$code] ?? 'No explanation given by Addons';

        return [
            'message' => $message,
            'http_code' => 460 + (int) $code,
            'technical_error_message' => self::$errors[$code] ?? 'Addons error',
        ];
    }

    private function getCustomizedMessage(array $addonsError): string
    {
        switch ($addonsError['http_code']) {
            case 460 + (int) self::WEBSITE_REFUSED:
                return "Your store's URL doesn't match the one provided when the module was purchased.";
            case 460 + (int) self::NO_SELECTABLE_BUSINESS_CARE:
                return 'You need an active Business Care subscription to download this module.';
            default:
                return sprintf(
                    'Cannot download the module from Addons : %s',
                    $addonsError['message'] ?? 'No further information'
                );
        }
    }
}
