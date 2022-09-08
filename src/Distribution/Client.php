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
use Shop;
use stdClass;

class Client
{
    public const HTTP_METHOD_GET = 'GET';
    public const HTTP_METHOD_POST = 'POST';

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
    protected $queryParameters = ['format' => 'json'];

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
        'shop_uuid',
        'shop_url',
        'isoLang',
    ];

    /**
     * @var string
     */
    private $shopUuid;

    /**
     * @var string
     */
    private $shopUrl;

    /**
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient, CacheProvider $cacheProvider)
    {
        $this->httpClient = $httpClient;
        $this->cacheProvider = $cacheProvider;
        $this->shopUuid = Config::getShopMboUuid();
        $shopId = (int) Context::getContext()->shop->id;
        $this->shopUrl = (new Shop($shopId))->getBaseUrl();
    }

    public function setDefaultParams(): void
    {
        $this->setQueryParams([
            'shop_uuid' => $this->shopUuid,
            'shop_url' => $this->shopUrl,
        ]);
        $this->defaultQueryParameters = $this->queryParameters;
    }

    /**
     * In case you reuse the Client, you may want to clean the previous parameters.
     */
    public function reset(): void
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
     * Get a new key from Distribution API.
     *
     * @return stdClass
     *
     * @throws GuzzleException
     */
    public function retrieveNewKey(): stdClass
    {
        return $this->processRequestAndReturn('shops/get-pub-key', null, self::HTTP_METHOD_GET);
    }

    /**
     * Register new Shop on Distribution API.
     *
     * @return stdClass
     *
     * @throws GuzzleException
     */
    public function registerShop(string $token): stdClass
    {
        $data = [
            'uuid' => $this->shopUuid,
            'shop_url' => $this->shopUrl,
            'admin_path' => sprintf('/%s/', trim(str_replace(_PS_ROOT_DIR_, '', _PS_ADMIN_DIR_), '/')),
            'mbo_version' => ps_mbo::VERSION,
            'ps_version' => _PS_VERSION_,
            'auth_cookie' => $token,
        ];

        return $this->processRequestAndReturn(
            'shops',
            null,
            self::HTTP_METHOD_POST,
            ['form_params' => $data]
        );
    }

    /**
     * Retrieve the user menu from NEST Api
     *
     * @return \stdClass
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getConf(): stdClass
    {
        $languageCode = Context::getContext()->language->getLanguageCode();
        $cacheKey = __METHOD__ . $languageCode . _PS_VERSION_;

        if ($this->cacheProvider->contains($cacheKey)) {
            return $this->cacheProvider->fetch($cacheKey);
        }

        $this->setQueryParams([
            'isoLang' => $languageCode,
        ]);
        $conf = $this->processRequestAndReturn('shops/conf');

        $this->cacheProvider->save($cacheKey, $conf, 60 * 60 * 24); // A day

        return $this->cacheProvider->fetch($cacheKey);
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
     * @return string
     *
     * @throws GuzzleException
     */
    private function processRequest(
        string $method = self::HTTP_METHOD_GET,
        string $uri = '',
        array $options = []
    ): string {
        $options['query'] = $this->queryParameters;

        return (string) $this->httpClient
            ->request($method, '/api/' . ltrim($uri, '/'), $options)
            ->getBody();
    }
}
