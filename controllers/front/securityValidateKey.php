<?php

use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Controller\AbstractApiController;
use PrestaShop\Module\Mbo\Api\Exception\QueryParamsException;
use PrestaShop\Module\Mbo\Api\Security\KeyProvider;

class ps_MboSecurityValidateKeyModuleFrontController extends AbstractApiController
{
    public $type = Config::API_AUTHORIZATION;

    /**
     * @return void
     */
    public function postProcess()
    {
        $cryptoKey = Tools::getValue('key');
        $cryptoKeyVersion = Tools::getValue('key_version');
        $mac = Tools::getValue('mac');

        if (empty($cryptoKey) || empty($cryptoKeyVersion) || empty($mac)) {
            $this->exitWithExceptionMessage(new QueryParamsException('Invalid URL Parameters', Config::INVALID_URL_QUERY));
        }

        /** @var KeyProvider $keyProvider */
        $keyProvider = $this->module->getService(KeyProvider::class);

        $key = $keyProvider->validateKey(
            $cryptoKey,
            $cryptoKeyVersion,
            $mac
        );

        $this->exitWithResponse($key);
    }
}
