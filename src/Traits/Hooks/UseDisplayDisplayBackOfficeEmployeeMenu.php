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

use PrestaShopBundle\Component\ActionBar\ActionsBarButton;
use PrestaShopBundle\Component\ActionBar\ActionsBarButtonsCollection;

trait UseDisplayDisplayBackOfficeEmployeeMenu
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
        if (!class_exists(ActionsBarButtonsCollection::class)
            || !class_exists(ActionsBarButton::class)
            || !($params['links'] instanceof ActionsBarButtonsCollection)) {
            return;
        }

        $links = [
            [
                'url' => 'https://www.prestashop.com/en/resources/documentations?utm_source=back-office&utm_medium=profile&utm_campaign=resources-en&utm_content=download17',
                'icon' => 'book',
                'label' => 'Resources',
            ],
            [
                'url' => 'https://www.prestashop.com/en/training?utm_source=back-office&utm_medium=profile&utm_campaign=training-en&utm_content=download17',
                'icon' => 'school',
                'label' => 'Training',
            ],
            [
                'url' => 'https://www.prestashop.com/en/experts?utm_source=back-office&utm_medium=profile&utm_campaign=expert-en&utm_content=download17',
                'icon' => 'person_pin_circle',
                'label' => 'Find an Expert',
            ],
            [
                'url' => 'https://addons.prestashop.com?utm_source=back-office&utm_medium=profile&utm_campaign=addons-en&utm_content=download17',
                'icon' => 'extension',
                'label' => 'PrestaShop Marketplace',
            ],
            [
                'url' => 'https://www.prestashop.com/en/contact?utm_source=back-office&utm_medium=profile&utm_campaign=help-center-en&utm_content=download17',
                'icon' => 'help',
                'label' => 'Help Center',
            ],
        ];

        foreach ($links as $link) {
            $params['links']->add(
                new ActionsBarButton(
                    __CLASS__,
                    [
                        'link' => $this->trans($link['url'], [], 'Admin.Navigation.Header'),
                        'icon' => $link['icon'],
                    ],
                    $this->trans($link['label'], [], 'Admin.Navigation.Header')
                )
            );
        }
    }
}
