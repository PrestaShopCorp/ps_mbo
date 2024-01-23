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

use PrestaShop\Module\Mbo\Distribution\Client;
use PrestaShop\Module\Mbo\Exception\ExpectedServiceNotFoundException;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Helpers\Version;
use PrestaShop\PrestaShop\Core\Action\ActionsBarButton;
use PrestaShop\PrestaShop\Core\Action\ActionsBarButtonsCollection;
use Symfony\Component\Routing\Router;

trait UseDisplayBackOfficeEmployeeMenu
{
    /**
     * Hook displayBackOfficeMenu.
     * Returns menu in BackOffice
     *
     * @param array $params
     *
     * @return void
     */
    public function hookDisplayBackOfficeEmployeeMenu(array $params): void
    {
        if (
            !class_exists(ActionsBarButtonsCollection::class)
            || !class_exists(ActionsBarButton::class)
            || !($params['links'] instanceof ActionsBarButtonsCollection)
        ) {
            return;
        }

        try {
            /** @var Client $apiClient */
            $apiClient = $this->get('mbo.cdc.client.distribution_api');

            /** @var Router $router */
            $router = $this->get('router');

            if (null === $apiClient || null === $router) {
                throw new ExpectedServiceNotFoundException(
                    'Some services not found in UseDisplayBackOfficeEmployeeMenu'
                );
            }

            $config = $apiClient->setRouter($router)->getEmployeeMenu();
            if (empty($config) || empty($config->userMenu) || !is_array($config->userMenu)) {
                return;
            }
            foreach ($config->userMenu as $link) {
                $versionFrom = Version::convertFromApi($link->ps_version_from);
                $versionTo = Version::convertFromApi($link->ps_version_to);
                if (
                    version_compare(_PS_VERSION_, $versionFrom, '<')
                    || version_compare(_PS_VERSION_, $versionTo, '>')
                ) {
                    continue;
                }
                $params['links']->add(
                    new ActionsBarButton(
                        __CLASS__,
                        [
                            'link' => $link->link,
                            'icon' => $link->icon,
                            'isExternalLink' => $link->is_external_link ?? true,
                        ],
                        $link->name
                    )
                );
            }
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);

            return;
        }
    }
}
