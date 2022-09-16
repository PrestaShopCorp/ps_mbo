<?php

use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Controller\AbstractAdminApiController;

/**
 * This controller only checks if the user is connected using the token given in parameter.
 * Note that if the token is valid, the user session is extended.
 */
class apiSecurityPsMboController extends AbstractAdminApiController
{
    public $type = Config::SECURITY_ME;

    /**
     * @return void
     */
    public function postProcess()
    {
        $this->exitWithResponse([
            'message' => 'User still connected',
        ]);
    }

    protected function authorize(): void
    {
    }
}
