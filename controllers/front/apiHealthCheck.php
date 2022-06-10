<?php

use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Controller\AbstractApiController;
use PrestaShop\Module\Mbo\Api\Repository\ServerInformationRepository;

class ps_MboApiHealthCheckModuleFrontController extends AbstractApiController
{
    public $type = Config::COLLECTION_SHOPS;

//    public function init()
//    {
//    }

    /**
     * @return void
     */
    public function postProcess()
    {
        /** @var ServerInformationRepository $serverInformationRepository */
        $serverInformationRepository = $this->module->getService(ServerInformationRepository::class);

        $status = $serverInformationRepository->getHealthCheckData();

        $this->exitWithResponse($status);
    }
}
