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

trait UseActionPresentModule
{
    /**
     * Update the badge images URLs from old medias to module folder.
     * Remove 404 images
     *
     * @param array $module
     *
     * @return void
     */
    public function hookActionPresentModule(array &$module): void
    {
        if (empty($module['presentedModule']['attributes']['badges'])) {
            return;
        }

        foreach ($module['presentedModule']['attributes']['badges'] as &$badge) {
            // Remove 404 images
            if (
                preg_match('#savetime-module-of-the-year.png$#i', $badge['img']) ||
                preg_match('#convert-partner.png$#i', $badge['img']) ||
                preg_match('#savetime-partner.png$#i', $badge['img'])
            ) {
                $badge['img'] = false;
                continue;
            }
            $badge['img'] = basename($badge['img']);
        }
    }
}
