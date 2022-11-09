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
