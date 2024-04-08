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

namespace PrestaShop\Module\Mbo\Addons;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PrestaShop\Module\Mbo\Helpers\AddonsApiHelper;
use stdClass;

class ApiClient
{
    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var array<string, string>
     */
    protected $queryParameters = ['format' => 'json'];

    protected $headers = [];

    /**
     * @var array<string, string>
     */
    protected $defaultQueryParameters;

    /**
     * @var array<int, string>
     */
    protected $possibleQueryParameters = [
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
     * @param Client $httpClient
     */
    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setDefaultParams(string $locale, $isoCode, ?string $domain, string $shopVersion)
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
        $this->headers = [];
    }

    /**
     * @return array<string, string>
     */
    public function getQueryParameters()
    {
        return $this->queryParameters;
    }

    public function getHeaders()
    {
        return array_merge($this->headers, AddonsApiHelper::addCustomHeaderIfNeeded());
    }

    /**
     * Check Addons client account credentials.
     *
     * @param array{username_addons: string, password_addons: string} $params
     *
     * @return stdClass
     */
    public function getCheckCustomer(array $params)
    {
        return $this->setQueryParams([
            'method' => 'check_customer',
        ] + $params)->processRequestAndReturn(null, self::HTTP_METHOD_POST);
    }

    /**
     * Check if a module is distributed by Addons.
     *
     * @param array{username_addons: string, password_addons: string, module_name: string, module_key: string} $params
     *
     * @return stdClass
     */
    public function getCheckModule(array $params)
    {
        return $this->setQueryParams([
            'method' => 'check',
        ] + $params)->processRequestAndReturn();
    }

    /**
     * @param array{iso_code: string} $params
     *
     * @return array
     */
    public function getNativesModules(array $params)
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'native',
        ] + $params)->processRequestAndReturn('modules');
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function getPreInstalledModules(array $params = [])
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'install-modules',
        ] + $params)->processRequestAndReturn('modules');
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function getMustHaveModules(array $params = [])
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'must-have',
        ] + $params)->processRequestAndReturn('modules');
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function getServices(array $params = [])
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'service',
        ] + $params)->processRequestAndReturn('services');
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function getCategories(array $params = [])
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'categories',
        ] + $params)->processRequestAndReturn('module');
    }

    /**
     * @param array{id_module: int} $params
     *
     * @return object|null
     */
    public function getModule(array $params)
    {
        $modules = $this->setQueryParams([
            'method' => 'listing',
            'action' => 'module',
        ] + $params)->processRequestAndReturn('modules');

        if (!isset($modules[0])) {
            return null;
        }

        return $modules[0];
    }

    /**
     * Call API for module ZIP content (= download).
     *
     * @param array{username_addons: string, password_addons: string, channel: string, id_module: int} $params
     *
     * @return string binary content (zip format)
     */
    public function getModuleZip(array $params)
    {
        return $this->setQueryParams([
            'method' => 'module',
        ] + $params)->processRequest(self::HTTP_METHOD_POST);
    }

    /**
     * @param array{username_addons: string, password_addons: string} $params
     *
     * @return array
     */
    public function getCustomerModules(array $params)
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'customer',
        ] + $params)->processRequestAndReturn('modules', self::HTTP_METHOD_POST);
    }

    /**
     * Get list of themes bought by customer.
     *
     * @param array{username_addons: string, password_addons: string} $params
     *
     * @return array
     */
    public function getCustomerThemes(array $params)
    {
        return $this->setQueryParams([
            'method' => 'listing',
            'action' => 'customer-themes',
        ] + $params)->processRequestAndReturn('themes', self::HTTP_METHOD_POST, new stdClass());
    }

    /**
     * Process the request with the current parameters, given the $method, and return the $attribute from
     * the response body, or the default fallback value $default.
     *
     * @param string|null $attributeToReturn
     * @param string $method
     * @param mixed $default
     *
     * @return mixed
     */
    public function processRequestAndReturn(
        string $attributeToReturn = null,
        string $method = self::HTTP_METHOD_GET,
        $default = []
    ) {
        $response = json_decode($this->processRequest($method));

        if (JSON_ERROR_NONE !== json_last_error()) {
            return $default;
        }

        if ($attributeToReturn) {
            if (!isset($response->{$attributeToReturn})) {
                return $default;
            }

            return $response->{$attributeToReturn};
        }

        return $response;
    }

    /**
     * Process the request with the current parameters, given the $method, return the body as string
     *
     * @return string
     *
     * @throws RequestException
     * @throws Exception
     */
    public function processRequest(string $method = self::HTTP_METHOD_GET)
    {
        $options = ['query' => $this->queryParameters];

        $headers = $this->getHeaders();
        if (!empty($headers)) {
            $options['headers'] = $headers;
        }

        switch ($method) {
            case self::HTTP_METHOD_GET:
                return (string) $this->httpClient
                    ->get(null, $options)
                    ->getBody();
            case self::HTTP_METHOD_POST:
                return (string) $this->httpClient
                    ->post(null, $options)
                    ->getBody();
            default:
                throw new Exception("Unknown or Not allowed method '{$method}'.");
        }
    }

    /**
     * @param Client $client
     *
     * @return $this
     */
    public function setClient(Client $client)
    {
        $this->httpClient = $client;

        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setQueryParams(array $params)
    {
        $filteredParams = array_intersect_key($params, array_flip($this->possibleQueryParameters));
        $this->queryParameters = array_merge($this->queryParameters, $filteredParams);

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }
}
