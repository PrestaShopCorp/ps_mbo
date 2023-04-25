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

namespace PrestaShop\Module\Mbo\Distribution\Config\Appliers;

use PrestaShop\Module\Mbo\Distribution\Config\Config;
use PrestaShop\Module\Mbo\Distribution\Config\Exception\InvalidConfigException;
use Tab;
use Validate;

class ThemeCatalogMenuConfigApplier implements ConfigApplierInterface
{
    public function supports(string $configKey): bool
    {
        return $configKey === 'theme_catalog_menu_link';
    }

    /**
     * @throws InvalidConfigException
     */
    public function apply(Config $config): bool
    {
        $themeCatalogMenu = $this->getThemeCatalogMenu();
        if (null === $themeCatalogMenu) {
            return true;
        }

        $configValue = $config->getConfigValue();
        if ('hide' === $configValue) {
            return $this->applyDown($themeCatalogMenu);
        } elseif ('show' === $configValue) {
            return $this->applyUp($themeCatalogMenu);
        } else {
            throw new InvalidConfigException(sprintf('%s is not a valid config value', $configValue));
        }
    }

    private function getThemeCatalogMenu()
    {
        $tab = Tab::getInstanceFromClassName('AdminPsMboTheme');

        return Validate::isLoadedObject($tab) ? $tab : null;
    }

    private function applyUp(Tab $themeCatalogMenu): bool
    {
        $themeCatalogMenu->enabled = true;
        $themeCatalogMenu->active = true;

        return $themeCatalogMenu->save();
    }

    private function applyDown(Tab $themeCatalogMenu): bool
    {
        $themeCatalogMenu->enabled = false;
        $themeCatalogMenu->active = false;

        return $themeCatalogMenu->save();
    }
}
