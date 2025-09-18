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

namespace PrestaShop\Module\Mbo\Traits;

use PrestaShop\Module\Mbo\Exception\ExpectedServiceNotFoundException;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

if (!defined('_PS_VERSION_')) {
    exit;
}

trait HaveCdcComponent
{
    public function smartyDisplayTpl(string $tpl, array $additionalParams = []): string
    {
        try {
            /** @var Router|null $router */
            $router = $this->get('router');

            if (null === $router) {
                throw new ExpectedServiceNotFoundException('Some services not found in HaveCdcComponent');
            }
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);

            return '';
        }

        $this->context->smarty->assign(
            array_merge([
                'cdcErrorUrl' => $router->generate('admin_mbo_module_cdc_error'),
            ], $additionalParams)
        );

        return $this->display($this->name, $tpl);
    }
}
