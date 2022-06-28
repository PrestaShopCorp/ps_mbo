<?php

use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Controller\AbstractFrontApiController;
use PrestaShop\Module\Mbo\Api\Repository\ServerInformationRepository;

class ps_MboFrontApiInfoModuleFrontController extends AbstractFrontApiController
{
    public $type = Config::COLLECTION_SHOPS;

    /**
     * @return void
     */
    public function postProcess()
    {
        /** @var ServerInformationRepository $serverInformationRepository */
        $serverInformationRepository = $this->module->getService(ServerInformationRepository::class);

        $status = $serverInformationRepository->getContextData();

        $this->exitWithResponse($status);
    }
}
