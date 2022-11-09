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

namespace PrestaShop\Module\Mbo\Distribution;

use Context;
use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use PrestaShop\Module\Mbo\Helpers\Config;
use ps_mbo;
use stdClass;

class Client
{
    public const HTTP_METHOD_GET = 'GET';
    public const HTTP_METHOD_POST = 'POST';
    public const HTTP_METHOD_PUT = 'PUT';
    public const HTTP_METHOD_DELETE = 'DELETE';
    /**
     * @var HttpClient
     */
    protected $httpClient;
    /**
     * @var CacheProvider
     */
    private $cacheProvider;
    /**
     * @var array<string, string>
     */
    protected $queryParameters = [];
    /**
     * @var array<int, string>
     */
    protected $possibleQueryParameters = [
        'format',
        'method',
        'action',
        'shop_uuid',
        'shop_url',
        'isoLang',
        'shopVersion',
    ];
    /**
     * @var array<string, string>
     */
    protected $headers = [];

    /**
     * @param HttpClient $httpClient
     * @param \Doctrine\Common\Cache\CacheProvider $cacheProvider
     */
    public function __construct(HttpClient $httpClient, CacheProvider $cacheProvider)
    {
        $this->httpClient = $httpClient;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * In case you reuse the Client, you may want to clean the previous parameters.
     */
    public function reset(): void
    {
        $this->queryParameters = [];
        $this->headers = [];
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

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * @param string $jwt
     *
     * @return $this
     */
    public function setBearer(string $jwt): self
    {
        return $this->setHeaders(['Authorization' => 'Bearer ' . $jwt]);
    }

    /**
     * Get a new key from Distribution API.
     *
     * @return stdClass
     *
     * @throws GuzzleException
     */
    public function retrieveNewKey(): stdClass
    {
        return $this->processRequestAndReturn('shops/get-pub-key');
    }

    /**
     * Register new Shop on Distribution API.
     *
     * @param array $params
     *
     * @return stdClass
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @usage \PrestaShop\Module\Mbo\Traits\HaveShopOnExternalService::registerShop
     */
    public function registerShop(array $params = []): stdClass
    {
        return $this->processRequestAndReturn(
            'shops',
            null,
            self::HTTP_METHOD_POST,
            ['form_params' => $this->mergeShopDataWithParams($params)]
        );
    }

    /**
     * Unregister a Shop on Distribution API.
     *
     * @return stdClass
     *
     * @throws GuzzleException
     */
    public function unregisterShop()
    {
        return $this->processRequestAndReturn(
            'shops/' . Config::getShopMboUuid(),
            null,
            self::HTTP_METHOD_DELETE
        );
    }

    /**
     * Update shop on Distribution API.
     *
     * @param array $params
     *
     * @return stdClass
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @usage \PrestaShop\Module\Mbo\Traits\HaveShopOnExternalService::updateShop
     */
    public function updateShop(array $params): stdClass
    {
        return $this->processRequestAndReturn(
            'shops/' . Config::getShopMboUuid(),
            null,
            self::HTTP_METHOD_PUT,
            ['form_params' => $this->mergeShopDataWithParams($params)]
        );
    }

    /**
     * Retrieve the user menu from NEST Api
     *
     * @return false|stdClass
     */
    public function getConf()
    {
        $languageIsoCode = Context::getContext()->language->getIsoCode();
        $cacheKey = __METHOD__ . $languageIsoCode . _PS_VERSION_;

        if ($this->cacheProvider->contains($cacheKey)) {
            return $this->cacheProvider->fetch($cacheKey);
        }

        $this->setQueryParams([
            'isoLang' => $languageIsoCode,
            'shopVersion' => _PS_VERSION_,
        ]);
        try {
            $conf = $this->processRequestAndReturn('shops/conf');
        } catch (\Throwable $e) {
            return false;
        }
        if (empty($conf)) {
            return false;
        }
        $this->cacheProvider->save($cacheKey, $conf, 60 * 60 * 24); // A day

        return $this->cacheProvider->fetch($cacheKey);
    }

    /**
     * Send a tracking to the API
     * Send it asynchronously to avoid blocking process for this feature
     *
     * @param array $eventData
     */
    public function trackEvent(array $eventData): void
    {
        try {
            $this->processRequestAndReturn(
                'shops/events',
                null,
                self::HTTP_METHOD_POST,
                ['form_params' => $eventData]
            );
        } catch (\Throwable $e) {
            // Do nothing if the tracking fails
        }
    }

    private function mergeShopDataWithParams(array $params): array
    {
        return array_merge([
            'uuid' => Config::getShopMboUuid(),
            'shop_url' => Config::getShopUrl(),
            'admin_path' => sprintf('/%s/', trim(str_replace(_PS_ROOT_DIR_, '', _PS_ADMIN_DIR_), '/')),
            'mbo_version' => ps_mbo::VERSION,
            'ps_version' => _PS_VERSION_,
        ], $params);
    }

    /**
     * Process the request with the current parameters, given the $method, and return the $attribute from
     * the response body, or the default fallback value $default.
     *
     * @param string $uri
     * @param string|null $attributeToReturn
     * @param string $method
     * @param array $options
     * @param mixed $default
     *
     * @return mixed
     *
     * @throws GuzzleException
     */
    private function processRequestAndReturn(
        string $uri,
        ?string $attributeToReturn = null,
        string $method = self::HTTP_METHOD_GET,
        array $options = [],
        $default = []
    ) {
        $response = json_decode($this->processRequest($method, $uri, $options));

        if (JSON_ERROR_NONE !== json_last_error()) {
            return $default;
        }

        if ($attributeToReturn) {
            return $response->{$attributeToReturn} ?? $default;
        }

        return $response;
    }

    /**
     * Process the request with the current parameters, given the $method, return the body as string
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     *
     * @return string
     *
     * @throws GuzzleException
     */
    private function processRequest(
        string $method = self::HTTP_METHOD_GET,
        string $uri = '',
        array $options = []
    ): string {
        $options = array_merge($options, [
            'query' => $this->queryParameters,
            'headers' => $this->headers,
        ]);

        return (string) $this->httpClient
            ->request($method, '/api/' . ltrim($uri, '/'), $options)
            ->getBody();
    }
}
