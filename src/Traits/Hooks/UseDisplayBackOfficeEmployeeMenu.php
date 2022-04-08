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

use PrestaShop\Module\Mbo\Addons\Provider\LinksProvider;
use PrestaShopBundle\Component\ActionBar\ActionsBarButton;
use PrestaShopBundle\Component\ActionBar\ActionsBarButtonsCollection;

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
        if (!class_exists(ActionsBarButtonsCollection::class)
            || !class_exists(ActionsBarButton::class)
            || !($params['links'] instanceof ActionsBarButtonsCollection)) {
            return;
        }

        /** @var LinksProvider $linksProvider */
        $linksProvider = $this->get('mbo.addons.links_provider');

        foreach ($linksProvider->getEmployeeMenuLinks() as $link) {
            $params['links']->add(
                new ActionsBarButton(
                    __CLASS__,
                    [
                        'link' => $link['url'],
                        'icon' => $link['icon'],
                    ],
                    $link['label'],
                )
            );
        }
    }
}
