<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace PrestaShop\Module\Mbo\Module;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PrestaShop\Module\Mbo\Module\Filter;

class FilterTest extends MockeryTestCase
{
    /**
     * @var Filter
     */
    private $filter;

    protected function setUp(): void
    {
        $this->filter = new Filter();
    }

    /**
     * @dataProvider getOrigins
     */
    public function testSetOrigin(int $origin, array $expectedValues)
    {
        $this->filter->setOrigin($origin);

        // Check after everything have been compute
        foreach ($expectedValues as $expected) {
            $this->assertTrue(
                $this->filter->hasOrigin($expected)
            );
        }
    }

    public function getOrigins()
    {
        return [
            [Filter\Origin::ALL & Filter\Origin::ADDONS_NATIVE, [Filter\Origin::ADDONS_NATIVE]],
            [Filter\Origin::ALL & Filter\Origin::DISK | Filter\Origin::ADDONS_NATIVE, [Filter\Origin::DISK, Filter\Origin::ADDONS_NATIVE]],
            [Filter\Origin::ADDONS_SERVICE ^ Filter\Origin::DISK, [Filter\Origin::DISK, Filter\Origin::ADDONS_SERVICE]],
        ];
    }


    /**
     * @dataProvider getStatuses
     */
    public function testSetStatus(int $status, array $expectedValues, bool $bool)
    {
        $this->filter->setStatus($status);

        // Check after everything have been compute
        foreach ($expectedValues as $expected) {
            $this->assertEquals(
                $bool,
                $this->filter->hasStatus($expected)
            );
        }
    }

    public function getStatuses()
    {
        return [
            [Filter\Status::ALL, [Filter\Status::ON_DISK, Filter\Status::INSTALLED, Filter\Status::ENABLED], true],
            [Filter\Status::ALL, [~Filter\Status::ON_DISK, ~Filter\Status::INSTALLED, ~Filter\Status::ENABLED], true],
            [Filter\Status::ALL & ~Filter\Status::ON_DISK, [~Filter\Status::ON_DISK], true],
            [Filter\Status::ALL & ~Filter\Status::ON_DISK, [Filter\Status::ON_DISK], false],
            [Filter\Status::ALL & ~Filter\Status::ON_DISK, [~Filter\Status::INSTALLED], false],
            [Filter\Status::ALL & ~Filter\Status::ON_DISK, [Filter\Status::INSTALLED], true],
        ];
    }


    /**
     * @dataProvider getTypes
     */
    public function testSetTypes(int $type, array $expectedValues)
    {
        $this->filter->setType($type);

        // Check after everything have been compute
        foreach ($expectedValues as $expected) {
            $this->assertTrue(
                $this->filter->hasType($expected)
            );
        }
    }

    public function getTypes()
    {
        return [
            [Filter\Type::ALL, [Filter\Type::THEME, Filter\Type::MODULE]],
            [Filter\Type::ALL & ~Filter\Type::MODULE, [Filter\Type::THEME, ~Filter\Type::MODULE]],
        ];
    }
}
