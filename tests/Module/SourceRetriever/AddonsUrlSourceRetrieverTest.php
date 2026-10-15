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

namespace PrestaShop\Module\Mbo\Tests\Module\SourceRetriever;

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Mbo\Module\SourceRetriever\AddonsUrlSourceRetriever;

class AddonsUrlSourceRetrieverTest extends TestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testAssertIsAddonsUrl(string $url, bool $expected): void
    {
        $this->assertSame($expected, AddonsUrlSourceRetriever::assertIsAddonsUrl($url));
    }

    public function urlProvider(): array
    {
        return [
            ['https://api-addons.prestashop.com/?method=module&id_module=1', true],
            ['https://api-addons.prestashop.com?method=module&format=json&channel=stable&id_module=46347', true],
            ['http://api-addons.prestashop.com/fakemod.zip', true],
            ['https://www.api-addons.prestashop.com/fakemod.zip', true],
            ['https://preprod-api-addons.prestashop.com/fakemod.zip', true],
            ['http://api-addons.prestashop.com@127.0.0.1:9999/fakemod.zip', false],
            ['http://user:api-addons.prestashop.com@127.0.0.1/fakemod.zip', false],
            ['http://evil.com#api-addons.prestashop.com', false],
            ['http://evil.com?api-addons.prestashop.com', false],
            ['http://evilapi-addons.prestashop.com.evil.com/fakemod.zip', false],
            ['http://127.0.0.1:9999/fakemod.zip', false],
        ];
    }
}
