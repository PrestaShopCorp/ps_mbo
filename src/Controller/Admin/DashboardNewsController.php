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

namespace PrestaShop\Module\Mbo\Controller\Admin;

use AdminController;
use PrestaShop\Module\Mbo\ExternalContentProvider\ExternalContentProviderInterface;
use PrestaShop\Module\Mbo\Service\News\NewsDataProvider;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Responsible of "Improve > Design > Themes Catalog" page display.
 */
class DashboardNewsController extends AdminController
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ExternalContentProviderInterface
     */
    protected $externalContentProvider;

    /**
     * @var NewsDataProvider
     */
    protected $newsDataProvider;

    /**
     * @param \PrestaShop\Module\Mbo\Service\News\NewsDataProvider $newsDataProvider
     */
    public function __construct(
        NewsDataProvider $newsDataProvider
    ) {
        parent::__construct();
        $this->newsDataProvider = $newsDataProvider;
    }

    /**
     * Returns last news from the blog
     */
    public function displayAjaxGetBlogRss()
    {
        $return = $this->newsDataProvider->getData($this->context->language->iso_code);

        // Response
        header('Content-Type: application/json');
        $this->ajaxRender(json_encode($return));
    }
}
