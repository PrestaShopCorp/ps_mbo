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
use Http\Discovery\Psr17FactoryDiscovery;
use Jean85\PrettyVersions;
use Module;
use Monolog\Logger;
use PrestaShop\Module\Mbo\Api\Config\Env;
use PrestaShop\Module\Mbo\Helpers\Config;
use Sentry\Client;
use Sentry\HttpClient\HttpClientFactory;
use Sentry\Options;
use Sentry\State\Scope;
use Sentry\Transport\DefaultTransportFactory;
use Sentry\UserDataBag;

class ErrorHandler implements ErrorHandlerInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var UserDataBag
     */
    protected $user;

    public function __construct(Module $module, Env $env, Logger $logger)
    {
        try {
            $shopUuid = Config::getShopMboUuid();

            $this->client = $this->getClient($logger, [
                'dsn' => $env->get('SENTRY_CREDENTIALS'),
                'tags' => [
                    'shop_id' => $shopUuid,
                    'ps_mbo_version' => $module::VERSION,
                    'php_version' => phpversion(),
                    'prestashop_version' => _PS_VERSION_,
                    'ps_mbo_is_enabled' => (string) Module::isEnabled($module->name),
                    'ps_mbo_is_installed' => (string) Module::isInstalled($module->name),
                    'env' => $env->get('SENTRY_ENVIRONMENT'),
                ],
            ]);

            $shopId = (int) Configuration::get('PS_SHOP_DEFAULT');
            $shopEmail = Configuration::get('PS_SHOP_EMAIL', null, null, $shopId);
            $this->user = new UserDataBag($shopUuid, $shopEmail); // we can add IP address and username later
        } catch (Exception $e) {
        }
    }

    /**
     * @throws Exception
     */
    public function handle(Exception $error, $code = null, ?bool $throw = true, ?array $data = []): void
    {
        if (!$this->client) {
            return;
        }

        $scope = new Scope();
        foreach ($data as $key => $value) {
            $scope->setContext($key, $value);
        }
        $scope->setUser($this->user);

        $this->client->captureException($error, $scope);
        $this->client->flush();
        if ($code && true === $throw) {
            http_response_code($code);
            throw $error;
        }
    }

    private function getClient(Logger $logger, array $options): Client
    {
        $uriFactory = $uriFactory ?? Psr17FactoryDiscovery::findUriFactory();
        $requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $responseFactory = $responseFactory ?? Psr17FactoryDiscovery::findResponseFactory();
        $streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();

        $sdkIdentifier = 'sentry.php';
        $sdkVersion = PrettyVersions::getVersion('sentry/sentry')->getPrettyVersion();

        $transportFactory = new DefaultTransportFactory(
            $streamFactory,
            $requestFactory,
            new HttpClientFactory(
                $uriFactory,
                $responseFactory,
                $streamFactory,
                null,
                $sdkIdentifier,
                $sdkVersion
            ),
            $logger
        );

        $options = new Options($options);

        return new Client(
            $options,
            $transportFactory->create($options),
            $sdkIdentifier,
            $sdkVersion,
            null,
            null,
            $logger
        );
    }

    /**
     * @return void
     */
    private function __clone()
    {
    }
}
