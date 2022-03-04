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

namespace PrestaShop\Module\Mbo\Tests\News;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use PrestaShop\Module\Mbo\News\News;
use PrestaShop\Module\Mbo\News\NewsBuilder;

class NewsBuilderTest extends MockeryTestCase
{
    public function testBuildWillReturnAValidNewsInstance()
    {
        $date = 'Mon, 28 Feb 2022 11:24:05 +0100';
        $title = 'CMS PrestaShop : guide d\'installation manuelle de votre boutique ecommerce';
        $description = 'Vous souhaitez installer PrestaShop pour lancer votre boutique en ligne ?';
        $link = 'https://www.prestashop.com/fr/blog/comment-installer-prestashop';
        $countryIsoCode = 'FR';
        $contextMode = 1;

        $tools = $this->getMockBuilder('PrestaShop\PrestaShop\Adapter\Tools')
            ->allowMockingUnknownTypes()
            ->setMethods([
                'displayDate',
                'truncateString',
            ])
            ->getMock();

        $tools
            ->method('displayDate')
            ->willReturn('2022-02-28 11:24:05');

        $tools
            ->method('truncateString')
            ->willReturn($description);

        $newsBuilder = \Mockery::mock(NewsBuilder::class . '[getAnalyticsParams,isHostContext]', [$tools])
            ->shouldAllowMockingProtectedMethods();
        $newsBuilder->shouldReceive('getAnalyticsParams')
            ->with($countryIsoCode, $contextMode);
        $newsBuilder->shouldReceive('isHostContext')
            ->with($contextMode);

        $news = $newsBuilder->build(
            $date,
            $title,
            $description,
            $link,
            $countryIsoCode,
            $contextMode
        );

        $this->assertInstanceOf(News::class, $news);

        $this->assertSame([
            'date' => '2022-02-28 11:24:05',
            'title' => 'CMS PrestaShop : guide d&#039;installation manuelle de votre boutique ecommerce',
            'short_desc' => $description,
            'link' => 'https://www.prestashop.com/fr/blog/comment-installer-prestashop?',
        ], $news->toArray());
    }
}
