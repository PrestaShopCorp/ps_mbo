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

namespace PrestaShop\Module\Mbo\Traits\Hooks;

use Exception;
use PrestaShop\Module\Mbo\Exception\ExpectedServiceNotFoundException;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use Twig\Environment;

trait UseDisplayAdminAfterHeader
{
    /**
     * Hook displayAdminAfterHeader.
     * Adds content in BackOffice after header section
     *
     * @return string
     */
    public function hookDisplayAdminAfterHeader($params): string
    {
        if (!$this->shouldDisplayModuleManagerMessage($params)) {
            return '';
        }

        return $this->renderModuleManagerMessage();
    }

    private function renderModuleManagerMessage(): string
    {
        try {
            /** @var Environment|null $twig */
            $twig = $this->get('twig');
            /** @var ContextBuilder|null $contextBuilder */
            $contextBuilder = $this->get(ContextBuilder::class);

            if (null === $contextBuilder || null === $twig) {
                throw new ExpectedServiceNotFoundException('Some services not found in UseDisplayAdminAfterHeader');
            }

            return $twig->render(
                '@Modules/ps_mbo/views/templates/hook/twig/module_manager_message.html.twig', [
                    'shop_context' => $contextBuilder->getViewContext(),
                ]
            );
        } catch (Exception $e) {
            ErrorHelper::reportError($e);

            return '';
        }
    }

    private function shouldDisplayModuleManagerMessage($params = []): bool
    {
        return in_array(
            $params['route'],
            [
                'admin_module_manage',
                'admin_module_notification',
                'admin_module_updates',
            ]
        );
    }
}
