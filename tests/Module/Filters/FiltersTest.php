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

namespace PrestaShop\Module\Mbo\Tests\Module\Filters;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use PrestaShop\Module\Mbo\Module\Filters;

class FiltersTest extends MockeryTestCase
{
    /**
     * @var Filters
     */
    protected $filters;

    protected function setUp(): void
    {
        $this->filters = new Filters();
    }

    /**
     * @dataProvider getOrigins
     */
    public function testSetOrigin(int $origin, array $expectedValues)
    {
        $this->filters->setOrigin($origin);

        // Check after everything have been computed
        foreach ($expectedValues as $expected) {
            $this->assertTrue(
                $this->filters->hasOrigin($expected)
            );
        }
    }

    public function getOrigins()
    {
        return [
            [Filters\Origin::ALL & Filters\Origin::ADDONS_NATIVE, [Filters\Origin::ADDONS_NATIVE]],
            [Filters\Origin::ALL & Filters\Origin::DISK | Filters\Origin::ADDONS_NATIVE, [Filters\Origin::DISK, Filters\Origin::ADDONS_NATIVE]],
            [Filters\Origin::ADDONS_SERVICE ^ Filters\Origin::DISK, [Filters\Origin::DISK, Filters\Origin::ADDONS_SERVICE]],
        ];
    }

    /**
     * @dataProvider getStatuses
     */
    public function testSetStatus(int $status, array $expectedValues, bool $bool)
    {
        $this->filters->setStatus($status);

        // Check after everything have been computed
        foreach ($expectedValues as $expected) {
            $this->assertEquals(
                $bool,
                $this->filters->hasStatus($expected)
            );
        }
    }

    public function getStatuses()
    {
        return [
            [Filters\Status::ALL, [Filters\Status::ON_DISK, Filters\Status::INSTALLED, Filters\Status::ENABLED], true],
            [Filters\Status::ALL, [~Filters\Status::ON_DISK, ~Filters\Status::INSTALLED, ~Filters\Status::ENABLED], true],
            [Filters\Status::ALL & ~Filters\Status::ON_DISK, [~Filters\Status::ON_DISK], true],
            [Filters\Status::ALL & ~Filters\Status::ON_DISK, [Filters\Status::ON_DISK], false],
            [Filters\Status::ALL & ~Filters\Status::ON_DISK, [~Filters\Status::INSTALLED], false],
            [Filters\Status::ALL & ~Filters\Status::ON_DISK, [Filters\Status::INSTALLED], true],
        ];
    }

    /**
     * @dataProvider getTypes
     */
    public function testSetTypes(int $type, array $expectedValues)
    {
        $this->filters->setType($type);

        // Check after everything have been computed
        foreach ($expectedValues as $expected) {
            $this->assertTrue(
                $this->filters->hasType($expected)
            );
        }
    }

    public function getTypes()
    {
        return [
            [Filters\Type::ALL, [Filters\Type::THEME, Filters\Type::MODULE]],
            [Filters\Type::ALL & ~Filters\Type::MODULE, [Filters\Type::THEME, ~Filters\Type::MODULE]],
        ];
    }
}
