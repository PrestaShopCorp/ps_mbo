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

namespace PrestaShop\Module\Mbo\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Mbo\Helpers\Version;

class VersionTest extends TestCase
{
    public function testConvertFromApi()
    {
        $this->assertSame(Version::convertFromApi(8000000), '8.0.0');

        $this->assertSame(Version::convertFromApi(8012001), '8.12.1');

        $this->assertSame(Version::convertFromApi(1007008007), '1.7.8.7');
    }

    public function testConvertToApi()
    {
        $this->assertSame(Version::convertToApi('8.0.0'), 8000000);

        $this->assertSame(Version::convertToApi('8.12.1'), 8012001);

        $this->assertSame(Version::convertToApi('1.7.8.7'), 1007008007);
    }
}
