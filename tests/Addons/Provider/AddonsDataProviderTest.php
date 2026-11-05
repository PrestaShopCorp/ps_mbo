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

namespace PrestaShop\Module\Mbo\Tests\Addons\Provider;

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Mbo\Addons\ApiClient;
use PrestaShop\Module\Mbo\Addons\Provider\AddonsDataProvider;
use PrestaShop\Module\Mbo\Addons\User\AddonsUser;

class AddonsDataProviderTest extends TestCase
{
    /**
     * @dataProvider shopUuidProvider
     */
    public function testModuleDownloadSendsAccountsShopUuidHeader(?string $shopUuid, array $expectedHeaders): void
    {
        $user = $this->createMock(AddonsUser::class);
        $user->method('isAuthenticated')->willReturn(true);
        $user->method('isConnectedOnPsAccounts')->willReturn(true);
        $user->method('getCredentials')->willReturn(['accounts_token' => 'token']);
        $user->method('getAccountsShopUuid')->willReturn($shopUuid);

        $sentHeaders = [];
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('setHeaders')->willReturnCallback(
            function (array $headers) use (&$sentHeaders, $apiClient) {
                $sentHeaders = array_merge($sentHeaders, $headers);

                return $apiClient;
            }
        );
        $apiClient->method('getModuleZip')->willReturn('zip');

        $provider = new AddonsDataProvider($apiClient, $user);
        $provider->request('module_download', ['id_module' => 1]);

        $this->assertSame($expectedHeaders, $sentHeaders);
    }

    public function shopUuidProvider(): array
    {
        return [
            'uuid available' => ['shop-uuid', ['Authorization' => 'Bearer token', 'accounts_shop_uuid' => 'shop-uuid']],
            'uuid null' => [null, ['Authorization' => 'Bearer token']],
        ];
    }
}
