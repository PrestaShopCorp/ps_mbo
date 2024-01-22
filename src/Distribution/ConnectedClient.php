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
use PrestaShop\Module\Mbo\Addons\User\UserInterface;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;

class ConnectedClient extends BaseClient
{
    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @param HttpClient $httpClient
     * @param CacheProvider $cacheProvider
     * @param UserInterface $user
     */
    public function __construct(HttpClient $httpClient, CacheProvider $cacheProvider, UserInterface $user)
    {
        parent::__construct($httpClient, $cacheProvider);
        $this->user = $user;
    }

    /**
     * Retrieve the modules list from NEST Api
     */
    public function getModulesList(): array
    {
        $languageIsoCode = Context::getContext()->language->getIsoCode();
        $countryIsoCode = mb_strtolower(Context::getContext()->country->iso_code);

        $userCacheKey = '';
        if ($this->user->isAuthenticated()) {
            $credentials = $this->user->getCredentials(true);

            if (null !== $credentials && array_key_exists('accounts_token', $credentials)) {
                $userCacheKey = md5($credentials['accounts_token']);

                $this->setQueryParams([
                    'accounts_token' => $credentials['accounts_token'],
                ]);
            }
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
            $modulesList = $this->processRequestAndDecode('modules');
        } catch (\Throwable $e) {
            ErrorHelper::reportError($e);
            return [];
        }
        if (empty($modulesList) || !is_array($modulesList)) {
            return [];
        }
        $this->cacheProvider->save($cacheKey, $modulesList, 60 * 60 * 24); // A day

        return $this->cacheProvider->fetch($cacheKey);
    }
}
