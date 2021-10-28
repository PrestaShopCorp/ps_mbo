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

namespace PrestaShop\Module\Mbo\Service\Addons;

use GuzzleHttp\Client;
use PrestaShop\PrestaShop\Adapter\Addons\AddonsDataProvider;
use stdClass;

class ApiClient
{
    /**
     * @var Client
     */
    private $addonsApiClient;

    /**
     * @var array<string, string>
     */
    private $queryParameters = ['format' => 'json'];

    /**
     * @var array<string, string>
     */
    private $defaultQueryParameters;

    /**
     * @var array<int, string>
     */
    private $possibleQueryParameters = [
        'format',
        'method',
        'action',
        'iso_lang',
        'iso_code',
        'version',
        'channel',
        'id_module',
        'module_key',
        'module_name',
        'shop_url',
        'username',
        'password',
    ];

    /**
     * @param Client $addonsApiClient
     */
    public function __construct(Client $addonsApiClient)
    {
        $this->addonsApiClient = $addonsApiClient;
    }

    public function setDefaultParams(string $locale, $isoCode, string $domain, string $shopVersion)
    {
        list($isoLang) = explode('-', $locale);
        $this->setQueryParams([
            'iso_lang' => $isoLang,
            'iso_code' => $isoCode,
            'version' => $shopVersion,
            'shop_url' => $domain,
        ]);
        $this->defaultQueryParameters = $this->queryParameters;
    }

    /**
     * In case you reuse the Client, you may want to clean the previous parameters.
     */
    public function reset()
    {
        $this->queryParameters = $this->defaultQueryParameters;
    }

    /**
     * @return array<string, string>
     */
    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    /**
     * Check Addons client account credentials.
     *
     * @param string $username
     * @param string $password
     *
     * @return object
     */
    public function getCheckCustomer(string $username, string $password): object
    {
        return $this->setQueryParams([
            'method' => 'check_customer',
            'username' => $username,
            'password' => $password,
        ])->getResponse('get');
    }

    /**
     * Check Addons client account credentials.
     *
     * @param string $username
     * @param string $password
     * @param string $moduleName
     * @param string $moduleKey
     *
     * @return object
     */
    public function getCheckModule(string $username, string $password, string $moduleName, string $moduleKey): object
    {
        return $this->setQueryParams([
            'method' => 'check',
            'username' => $username,
            'password' => $password,
            'module_name' => $moduleName,
            'module_key' => $moduleKey,
        ])->getResponse('get');
    }

    /**
     * @return array
     */
    public function getNativesModules(): array
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'native',
        ])->getResponseAttribute('get', 'modules');
    }

    /**
     * @return array
     */
    public function getPreInstalledModules(): array
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'install-modules',
        ])->getResponseAttribute('get', 'modules');
    }

    /**
     * @return array
     */
    public function getMustHaveModules(): array
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'must-have',
        ])->getResponseAttribute('get', 'modules');
    }

    /**
     * @return array
     */
    public function getServices(): array
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'service',
        ])->getResponseAttribute('get', 'services');
    }

    /**
     * @return array
     */
    public function getCategories(): array
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'categories',
        ])->getResponseAttribute('get', 'module');
    }

    /**
     * @param int $moduleId
     *
     * @return object|null
     */
    public function getModule(int $moduleId): ?object
    {
        $modules = $this->setQueryParams([
            'method' => 'listing',
            'action' => 'module',
            'id_module' => $moduleId,
        ])->getResponseAttribute('get', 'modules');

        return $modules[0] ?? null;
    }

    /**
     * Call API for module ZIP content (= download).
     *
     * @param int $moduleId
     * @param string $moduleChannel
     *
     * @return string binary content (zip format)
     */
    public function getModuleZip(int $moduleId, string $moduleChannel = AddonsDataProvider::ADDONS_API_MODULE_CHANNEL_STABLE): string
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'channel' => $moduleChannel,
            'id_module' => $moduleId,
        ])->getResponse('post');
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return array
     */
    public function getCustomerModules(string $username, string $password): array
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'customer',
            'username' => $username,
            'password' => $password,
        ])->getResponseAttribute('post', 'modules');
    }

    /**
     * Get list of themes bought by customer.
     *
     * @param string $username
     * @param string $password
     *
     * @return array
     */
    public function getCustomerThemes(string $username, string $password): array
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'customer-themes',
            'username' => $username,
            'password' => $password,
        ])->getResponseAttribute('post', 'themes', new stdClass());
    }

    /**
     * Process the request with the current parameters, given the $method, return the body
     *
     * @return mixed
     */
    public function getResponse(string $method)
    {
        return json_decode(
            (string) $this->addonsApiClient
                ->{$method}('', ['query' => $this->queryParameters])
                ->getBody()
        );
    }

    /**
     * Process the request with the current parameters, given the $method, and return the $attribute from
     * the response body, or the default fallback value $default.
     *
     * @param string $method
     * @param string $attribute
     * @param mixed $default
     *
     * @return mixed
     */
    public function getResponseAttribute(string $method, string $attribute, $default = [])
    {
        $response = $this->getResponse($method);

        return $response->{$attribute} ?? $default;
    }

    /**
     * @param Client $client
     *
     * @return $this
     */
    public function setClient(Client $client): self
    {
        $this->addonsApiClient = $client;

        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setQueryParams(array $params): self
    {
        $filteredParams = array_intersect_key($params, array_flip($this->possibleQueryParameters));
        $this->queryParameters = array_merge($this->queryParameters, $filteredParams);

        return $this;
    }
}
