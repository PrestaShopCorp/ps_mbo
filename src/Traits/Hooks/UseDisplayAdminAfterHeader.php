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
use Tools;
use Twig\Environment;

trait UseDisplayAdminAfterHeader
{
    /**
     * Hook displayAdminAfterHeader.
     * Adds content in BackOffice after header section
     *
     * @return string
     */
    public function hookDisplayAdminAfterHeader(): string
    {
        $shouldDisplayMboUserExplanation = $this->shouldDisplayMboUserExplanation();
        $shouldDisplayModuleManagerMessage = $this->shouldDisplayModuleManagerMessage();

        if (!$shouldDisplayMboUserExplanation && !$shouldDisplayModuleManagerMessage) {
            return '';
        }

        if ($shouldDisplayMboUserExplanation) {
            return $this->renderMboUserExplanation();
        }

        if ($shouldDisplayModuleManagerMessage) {
            return $this->renderModuleManagerMessage();
        }

        return '';
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function bootUseDisplayAdminAfterHeader(): void
    {
        if (method_exists($this, 'addAdminControllerMedia')) {
            $this->addAdminControllerMedia('loadMediaDisplayAdminAfterHeader');
        }
    }

    /**
     * Add JS and CSS file
     *
     * @return void
     *
     * @see \PrestaShop\Module\Mbo\Traits\Hooks\UseActionAdminControllerSetMedia
     */
    protected function loadMediaDisplayAdminAfterHeader(): void
    {
        if ($this->shouldDisplayMboUserExplanation()) {
            $this->context->controller->addJs(
                sprintf('%sviews/js/mbo-user-explanation.js?v=%s', $this->getPathUri(), $this->version)
            );
            $this->context->controller->addCSS(
                sprintf('%sviews/css/mbo-user-explanation.css?v=%s', $this->getPathUri(), $this->version)
            );
        }
    }

    private function renderMboUserExplanation(): string
    {
        try {
            /** @var Environment $twig */
            $twig = $this->get('twig');

            return $twig->render(
                '@Modules/ps_mbo/views/templates/hook/twig/explanation_mbo_employee.html.twig', [
                    'title' => $this->trans(
                        'Why is there a "PrestaShop Marketplace" employee?',
                        [],
                        'Modules.Mbo.Global'
                    ),
                    'message' => $this->trans('MBO employee explanation', [], 'Modules.Mbo.Global'),
                ]
            );
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
            return '';
        }
    }

    private function renderModuleManagerMessage(): string
    {
        try {
            /** @var Environment $twig */
            $twig = $this->get('twig');
            /** @var ContextBuilder $contextBuilder */
            $contextBuilder = $this->get('mbo.cdc.context_builder');

            if (null === $contextBuilder || null === $twig) {
                throw new ExpectedServiceNotFoundException(
                    'Some services not found in UseDisplayAdminAfterHeader'
                );
            }

            return $twig->render(
                '@Modules/ps_mbo/views/templates/hook/twig/module_manager_message.html.twig', [
                    'shop_context' => $contextBuilder->getViewContext(),
                    'title' => $this->trans(
                        'Why is there a "PrestaShop Marketplace" employee?',
                        [],
                        'Modules.Mbo.Global'
                    ),
                    'message' => $this->trans('MBO employee explanation', [], 'Modules.Mbo.Global'),
                ]
            );
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
            return '';
        }
    }

    private function shouldDisplayMboUserExplanation(): bool
    {
        if (Tools::getValue('controller') !== "AdminEmployees") {
            return false;
        }

        try {
            $requestStack = $this->get('request_stack');
            if (null === $requestStack || null === $request = $requestStack->getCurrentRequest()) {
                throw new Exception('Unable to get request');
            }
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
            return false;
        }

        // because admin_employee_index and admin_employee_edit are in the same controller AdminEmployees
        return 'admin_employees_index' === $request->get('_route');
    }

    private function shouldDisplayModuleManagerMessage(): bool
    {
        if (
            !in_array(
                Tools::getValue('controller'),
                [
                    "AdminModulesManage",
                    "AdminModulesNotifications",
                    "AdminModulesUpdates",
                ]
            )
        ) {
            return false;
        }

        try {
            $requestStack = $this->get('request_stack');
            if (null === $requestStack || null === $request = $requestStack->getCurrentRequest()) {
                throw new Exception('Unable to get request');
            }
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
            return false;
        }

        // because admin_employee_index and admin_employee_edit are in the same controller AdminEmployees
        return in_array($request->get('_route'), [
            'admin_module_manage',
            'admin_module_notification',
            'admin_module_updates',
        ]);
    }
}
