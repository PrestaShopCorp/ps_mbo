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
use GuzzleHttp\Exception\GuzzleException;
use PrestaShop\Module\Mbo\Addons\User\UserInterface;
use PrestaShop\Module\Mbo\Helpers\Config;
use stdClass;

class Client extends BaseClient
{
<<<<<<< HEAD
=======
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
     * @var UserInterface
     */
    private $user;
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
        'addons_username',
        'addons_pwd',
    ];
    /**
     * @var array<string, string>
     */
    protected $headers = [];

    /**
     * @param HttpClient $httpClient
     * @param \Doctrine\Common\Cache\CacheProvider $cacheProvider
     */
    public function __construct(HttpClient $httpClient, CacheProvider $cacheProvider, UserInterface $user)
    {
        $this->httpClient = $httpClient;
        $this->cacheProvider = $cacheProvider;
        $this->user = $user;
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

>>>>>>> dc25ea4 (refactor: :sparkles: Change calls from addons to Nest to retrieve modules)
    /**
     * Get a new key from Distribution API.
     *
     * @return stdClass
     *
     * @throws GuzzleException
     */
    public function retrieveNewKey(): stdClass
    {
        return $this->processRequestAndDecode('shops/get-pub-key');
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
        return $this->processRequestAndDecode(
            'shops',
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
        return $this->processRequestAndDecode(
            'shops/' . Config::getShopMboUuid(),
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
        return $this->processRequestAndDecode(
            'shops/' . Config::getShopMboUuid(),
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
            $conf = $this->processRequestAndDecode('shops/conf');
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
     * Retrieve the modules list from NEST Api
     */
    public function getModulesList(): array
    {
        $languageIsoCode = Context::getContext()->language->getIsoCode();
        $countryIsoCode = mb_strtolower(Context::getContext()->country->iso_code);

        $userCacheKey = '';
        $credentials = [];
        if ($this->user->isAuthenticated()) {
            $credentials = $this->user->getCredentials();
            $userCacheKey = md5($credentials['username'] . $credentials['password']);
            $this->setQueryParams([
               'addons_username' => $credentials['username'],
               'addons_pwd' => $credentials['password'],
            ]);
        }

        $cacheKey = __METHOD__ . $languageIsoCode . $countryIsoCode . $userCacheKey . _PS_VERSION_;

        if ($this->cacheProvider->contains($cacheKey)) {
            return $this->cacheProvider->fetch($cacheKey);
        }

        $this->setQueryParams([
            'iso_lang' => $languageIsoCode,
            'iso_code' => $countryIsoCode,
            'ps_version' => _PS_VERSION_,
            'shop_url' => Config::getShopUrl(),
        ]);
        try {
            $modulesList = $this->processRequestAndReturn('modules');
        } catch (\Throwable $e) {
            return [];
        }
        if (empty($modulesList) || !is_array($modulesList)) {
            return [];
        }
        $this->cacheProvider->save($cacheKey, $modulesList, 60 * 60 * 24); // A day

        return $this->cacheProvider->fetch($cacheKey);
    }

    /**
     * Retrieve API config from Distribution API.
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @usage \PrestaShop\Module\Mbo\Traits\HaveShopOnExternalService::registerShop
     */
    public function getApiConf(): array
    {
        return $this->processRequestAndDecode('shops/conf-mbo');
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
            $this->processRequestAndDecode(
                'shops/events',
                self::HTTP_METHOD_POST,
                ['form_params' => $eventData]
            );
        } catch (\Throwable $e) {
            // Do nothing if the tracking fails
        }
    }
}
