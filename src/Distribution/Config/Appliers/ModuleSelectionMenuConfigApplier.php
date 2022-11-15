<?php

namespace PrestaShop\Module\Mbo\Distribution\Config\Appliers;

use PrestaShop\Module\Mbo\Distribution\Config\Config;
use PrestaShop\Module\Mbo\Distribution\Config\Exception\InvalidConfigException;
use Tab;
use Validate;

class ModuleSelectionMenuConfigApplier implements ConfigApplierInterface
{
    public function supports(string $configKey): bool
    {
        return $configKey === 'module_selection_menu_link';
    }

    /**
     * @throws InvalidConfigException
     */
    public function apply(Config $config): bool
    {
        $moduleSelectionMenu = $this->getModuleSelectionMenu();
        if (null === $moduleSelectionMenu) {
            return true;
        }

        $configValue = $config->getConfigValue();
        if ('hide' === $configValue) {
            return $this->applyDown($moduleSelectionMenu);
        } elseif ('show' === $configValue) {
            return $this->applyUp($moduleSelectionMenu);
        } else {
            throw new InvalidConfigException(sprintf('%s is not a valid config value', $configValue));
        }
    }

    private function getModuleSelectionMenu()
    {
        $tab = Tab::getInstanceFromClassName('AdminPsMboSelection');

        return Validate::isLoadedObject($tab) ? $tab : null;
    }

    private function applyUp(Tab $moduleSelectionMenu): bool
    {
        $moduleSelectionMenu->enabled = true;
        $moduleSelectionMenu->active = true;

        return $moduleSelectionMenu->save();
    }

    private function applyDown(Tab $moduleSelectionMenu): bool
    {
        $moduleSelectionMenu->enabled = false;
        $moduleSelectionMenu->active = false;

        return $moduleSelectionMenu->save();
    }
}
