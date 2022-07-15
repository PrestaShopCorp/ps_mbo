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

namespace PrestaShop\Module\Mbo\Api\Handler\ErrorHandler;

use Configuration;
use Exception;
use Module;
use PrestaShop\Module\Mbo\Api\Config\Env;
use Raven_Client;

class ErrorHandler implements ErrorHandlerInterface
{
    /**
     * @var ?Raven_Client
     */
    protected $client;

    public function __construct(Module $module, Env $env)
    {
        try {
            $shopUuid = Configuration::get('PS_MBO_SHOP_ADMIN_UUID');

            $this->client = new Raven_Client(
                $env->get('SENTRY_CREDENTIALS'),
                [
                    'level' => 'warning',
                    'tags' => [
                        'shop_id' => $shopUuid,
                        'ps_mbo_version' => $module::VERSION,
                        'php_version' => phpversion(),
                        'prestashop_version' => _PS_VERSION_,
                        'ps_mbo_is_enabled' => Module::isEnabled($module->name),
                        'ps_mbo_is_installed' => Module::isInstalled($module->name),
                        'env' => $env->get('SENTRY_ENVIRONMENT'),
                    ],
                ]
            );
            $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
            $shopEmail = Configuration::get('PS_SHOP_EMAIL', null, null, $idShop);
            $this->client->set_user_data($shopUuid, $shopEmail);
        } catch (Exception $e) {
        }
    }

    /**
     * @throws Exception
     */
    public function handle(Exception $error, $code = null, ?bool $throw = true, ?array $data = null): void
    {
        if (!$this->client) {
            return;
        }
        $this->client->captureException($error, $data);
        if ($code && true === $throw) {
            http_response_code($code);
            throw $error;
        }
    }

    /**
     * @return void
     */
    private function __clone()
    {
    }
}
