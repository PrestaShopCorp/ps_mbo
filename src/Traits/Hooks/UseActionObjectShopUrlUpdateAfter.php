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

use PrestaShop\Module\Mbo\Helpers\Config;

trait UseActionObjectShopUrlUpdateAfter
{
    /**
     * @param array $params
     *
     * @return bool
     */
    public function hookActionObjectShopUrlUpdateAfter(array $params): bool
    {
        if ($params['object']->main) {
            // Clear cache to be sure to load correctly the shop with good data whe building the service later
            \Cache::clean('Shop::setUrl_' . (int) $params['object']->id_shop);

            if (Config::isUsingSecureProtocol()) {
                $url = 'https://' . preg_replace('#https?://#', '', $params['object']->domain_ssl);
            } else {
                $url = 'http://' . preg_replace('#https?://#', '', $params['object']->domain);
            }

            $this->updateShop([
                'shop_url' => $url,
            ]);
        }

        return true;
    }
}
