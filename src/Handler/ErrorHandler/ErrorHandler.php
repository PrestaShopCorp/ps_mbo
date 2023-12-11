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

namespace PrestaShop\Module\Mbo\Handler\ErrorHandler;

use Exception;
use Sentry\Client;
use Sentry\State\Scope;
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

    /**
     * @var array|false|string
     */
    protected $dsn;

    public function __construct()
    {
        $this->dsn = getenv('SENTRY_CREDENTIALS');

        if (empty($this->dsn)) {
            return;
        }

        try {
            \Sentry\init([
                'dsn' => $this->dsn,
                'release' => \ps_mbo::VERSION,
                'environment' => getenv('SENTRY_ENVIRONMENT'),
                'traces_sample_rate' => 0.5,
                'sample_rate' => 0.5,
            ]);

            \Sentry\configureScope(function (Scope $scope): void {
                $scope->setContext('shop info', [
                    'prestashop_version' => _PS_VERSION_,
                    'mbo_cdc_url' => getenv('MBO_CDC_URL'),
                    'distribution_api_url' => getenv('DISTRIBUTION_API_URL'),
                    'addons_api_url' => getenv('ADDONS_API_URL'),
                ]);
            });
        } catch (Exception $e) {
            // Do nothing here, Sentry seems not working well
        }
    }

    /**
     * {@inheritDoc}
     */
    public function handle(Exception $error, ?array $data = []): void
    {
        if (empty($this->dsn)) {
            return;
        }

        try {
            if (!empty($data)) {
                \Sentry\configureScope(function (Scope $scope) use ($data): void {
                    $scope->setContext('Additional data', $data);
                });
            }

            \Sentry\captureException($error);
        } catch (Exception $e) {
            // Do nothing here, Sentry seems not working well
        }
    }
}
