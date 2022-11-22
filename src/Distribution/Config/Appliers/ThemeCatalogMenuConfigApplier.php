<?php

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
