<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Mbo\Tab;

use PrestaShop\Module\Mbo\ModuleCollectionDataProvider;
use PrestaShop\Module\Mbo\RecommendedModule\RecommendedModule;
use PrestaShop\Module\Mbo\RecommendedModule\RecommendedModuleCollection;

class TabCollectionFactory implements TabCollectionFactoryInterface
{
    private $moduleCollectionDataProvider;

    /**
     * Constructor.
     *
     * @param ModuleCollectionDataProvider $moduleCollectionDataProvider
     */
    public function __construct(ModuleCollectionDataProvider $moduleCollectionDataProvider)
    {
        $this->moduleCollectionDataProvider = $moduleCollectionDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildFromArray(array $data)
    {
        $tabCollection = new TabCollection();

        if (empty($data)) {
            return $tabCollection;
        }

        $modulesData = $this->moduleCollectionDataProvider->getData($this->getModuleNames($data));

        if (empty($modulesData)) {
            return $tabCollection;
        }

        foreach ($data as $tabClassName => $tabData) {
            $recommendedModuleCollection = new RecommendedModuleCollection();

            foreach ($tabData['recommendedModules'] as $position => $moduleName) {
                if (isset($modulesData[$moduleName])) {
                    $recommendedModule = new RecommendedModule();
                    $recommendedModule->setModuleName($moduleName);
                    $recommendedModule->setPosition((int) $position);
                    $recommendedModule->setInstalled((bool) $modulesData[$moduleName]['database']['installed']);
                    $recommendedModule->setModuleData($modulesData[$moduleName]);
                    $recommendedModuleCollection->addRecommendedModule($recommendedModule);
                }
            }

            if (!$recommendedModuleCollection->isEmpty()) {
                $recommendedModuleCollection->sortByPosition();

                $tab = new Tab();
                $tab->setLegacyClassName($tabClassName);
                $tab->setDisplayMode($tabData['displayMode']);
                $tab->setRecommendedModules($recommendedModuleCollection);

                $tabCollection->addTab($tab);
            }
        }

        return $tabCollection;
    }

    /**
     * @param array $data
     *
     * @return string[]
     */
    private function getModuleNames(array $data)
    {
        $moduleNames = [];

        foreach ($data as $tabData) {
            foreach ($tabData['recommendedModules'] as $moduleName) {
                $moduleNames[] = $moduleName;
            }
        }

        return array_unique($moduleNames);
    }
}
