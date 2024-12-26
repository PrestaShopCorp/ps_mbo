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

use Doctrine\Common\Cache\CacheProvider;
use PrestaShop\Module\Mbo\Exception\ClientRequestException;
use PrestaShop\Module\Mbo\Helpers\Config;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class BaseClient
{
    public const HTTP_METHOD_GET = 'GET';
    public const HTTP_METHOD_POST = 'POST';
    public const HTTP_METHOD_PUT = 'PUT';
    public const HTTP_METHOD_DELETE = 'DELETE';

    protected string $apiUrl;

    /**
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * @var RequestFactoryInterface
     */
    protected RequestFactoryInterface $requestFactory;
    /**
     * @var CacheProvider
     */
    protected $cacheProvider;
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
        'ps_version',
        'iso_lang',
        'iso_code',
        'accounts_token',
        'addons_username',
        'addons_pwd',
        'catalogUrl',
    ];
    /**
     * @var array<string, string>
     */
    protected $headers = [];

    public function __construct(
        string $apiUrl,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        CacheProvider $cacheProvider,
    ) {
        $this->apiUrl = $apiUrl;
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
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

    protected function mergeShopDataWithParams(array $params): array
    {
        return array_merge([
            'uuid' => Config::getShopMboUuid(),
            'shop_url' => Config::getShopUrl(),
            'admin_path' => sprintf('/%s/', trim(str_replace(_PS_ROOT_DIR_, '', _PS_ADMIN_DIR_), '/')),
            'mbo_version' => \ps_mbo::VERSION,
            'ps_version' => _PS_VERSION_,
        ], $params);
    }

    /**
     * Process the request with the current parameters, given the $method, and return the $attribute from
     * the response body, or the default fallback value $default.
     *
     * @param string $uri
     * @param array $options
     * @param string $method
     * @param mixed $default
     *
     * @return mixed
     *
     * @throws ClientExceptionInterface
     */
    protected function processRequestAndDecode(
        string $uri,
        string $method = self::HTTP_METHOD_GET,
        array $options = [],
        $default = [],
    ) {
        $response = json_decode($this->processRequest($uri, $method, $options));

        if (JSON_ERROR_NONE !== json_last_error()) {
            return $default;
        }

        return $response;
    }

    /**
     * Process the request with the current parameters, given the $method, return the body as string
     *
     * @param string $uri
     * @param string $method
     * @param array $options
     *
     * @return string
     *
     * @throws ClientExceptionInterface
     * @throws ClientRequestException
     */
    protected function processRequest(
        string $uri = '',
        string $method = self::HTTP_METHOD_GET,
        array $options = [],
    ): string {
        $queryString = !empty($this->queryParameters) ? '?' . http_build_query($this->queryParameters) : '';
        $request = $this->requestFactory->createRequest($method, $this->apiUrl . '/api/' . ltrim($uri, '/') . $queryString);
        if (empty($this->headers['Content-Type'])) {
            $this->headers['Accept'] = 'application/json';
            $this->headers['Content-Type'] = 'application/json';
        }
        foreach ($this->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (!empty($options['form_params'])) {
            if ($this->headers['Content-Type'] === 'application/x-www-form-urlencoded') {
                $request = $request->withBody($this->createStream(urlencode(serialize($options['form_params']))));
            } else {
                $request = $request->withBody($this->createStream(json_encode($options['form_params'])));
            }
        }

        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new ClientRequestException($response->getReasonPhrase(), $response->getStatusCode());
        }

        return $response->getBody()->getContents();
    }

    private function createStream(string $content): \Psr\Http\Message\StreamInterface
    {
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        return $psr17Factory->createStream($content);
    }
}
