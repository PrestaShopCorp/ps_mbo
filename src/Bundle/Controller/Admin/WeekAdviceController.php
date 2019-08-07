<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Mbo\Bundle\Controller\Admin;

use PrestaShop\Module\Mbo\Adapter\WeekAdviceProvider;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class WeekAdviceController extends FrameworkBundleAdminController
{
    /**
     * @return JsonResponse
     */
    public function indexAction()
    {
        $response = new JsonResponse();

        try {
            $weekAdvice = $this->getWeekAdviceProvider()->getWeekAdvice();
            $response->setData([
                'content' => [
                    'advice' => $weekAdvice->getAdvice(),
                    'link' => $weekAdvice->getLink(),
                ],
                'success' => true,
            ]);
        } catch (ServiceUnavailableHttpException $exception) {
            $response->setData([
                'content' => $exception->getMessage(),
                'success' => false,
            ]);
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->add($exception->getHeaders());
        }

        return $response;
    }

    /**
     * @return WeekAdviceProvider
     */
    private function getWeekAdviceProvider()
    {
        return $this->get('mbo.week_advice.provider');
    }
}
