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

namespace PrestaShop\Module\Mbo\Tests\Distribution;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Firebase\JWT\JWT;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Mbo\Addons\User\AddonsUser;
use PrestaShop\Module\Mbo\Addons\User\AddonsUserProvider;
use PrestaShop\Module\Mbo\Distribution\ConnectedClient;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\PrestaShop\Core\Context\CountryContext;
use PrestaShop\PrestaShop\Core\Context\LanguageContext;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ConnectedClientTest extends TestCase
{
    private CacheProvider $cache;

    private int $apiCalls = 0;

    protected function setUp(): void
    {
        $cache = DoctrineProvider::wrap(new ArrayAdapter());
        \assert($cache instanceof CacheProvider);
        $this->cache = $cache;
        // Avoid the database lookup of the shop URL
        (new \ReflectionProperty(Config::class, 'SHOP_URL'))->setValue(null, 'https://shop.test');
    }

    protected function tearDown(): void
    {
        Config::resetConfigValues();
    }

    public function testModulesListIsCachedAcrossTokenRenewalsOfTheSameAccount(): void
    {
        $this->getModulesList($this->token('account-1', 1000));
        $this->getModulesList($this->token('account-1', 4600));

        $this->assertSame(1, $this->apiCalls);
    }

    public function testModulesListIsNotSharedBetweenAccounts(): void
    {
        $this->getModulesList($this->token('account-1', 1000));
        $this->getModulesList($this->token('account-2', 1000));

        $this->assertSame(2, $this->apiCalls);
    }

    public function testUndecodableTokenIsCachedOnTheTokenItself(): void
    {
        $this->assertCount(1, $this->getModulesList('not-a-jwt'));
        $this->getModulesList('not-a-jwt');
        $this->getModulesList('another-one');

        $this->assertSame(2, $this->apiCalls);
    }

    /**
     * One BO request: a new client with the given accounts token, sharing the cache.
     */
    private function getModulesList(string $accountsToken): array
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(function () {
            ++$this->apiCalls;

            return new Response(200, [], '[{"name":"a_module"}]');
        });

        $user = $this->createMock(AddonsUser::class);
        $user->method('isAuthenticated')->willReturn(true);
        $user->method('getCredentials')->willReturn(['accounts_token' => $accountsToken]);
        $userProvider = $this->createMock(AddonsUserProvider::class);
        $userProvider->method('getUser')->willReturn($user);

        $language = $this->createMock(LanguageContext::class);
        $language->method('getIsoCode')->willReturn('fr');
        $country = $this->createMock(CountryContext::class);
        $country->method('getIsoCode')->willReturn('FR');

        $client = new ConnectedClient('https://api.test', $httpClient, new Psr17Factory(), $this->cache, $userProvider, $language, $country);

        return $client->getModulesList();
    }

    /**
     * An accounts token as renewed every hour: same owner ("sub"), new issue time.
     */
    private function token(string $sub, int $iat): string
    {
        return JWT::urlsafeB64Encode('{"alg":"RS256"}') . '.' . JWT::urlsafeB64Encode(json_encode(['sub' => $sub, 'iat' => $iat])) . '.signature';
    }
}
