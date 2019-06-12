<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Mbo\Factory;

use PrestaShop\Module\Mbo\Adapter\ModulesDataProvider;
use PrestaShop\Module\Mbo\RecommendedModules\RecommendedModule;
use PrestaShop\Module\Mbo\RecommendedModules\RecommendedModules;
use PrestaShop\Module\Mbo\TabsRecommendedModules\TabRecommendedModules;
use PrestaShop\Module\Mbo\TabsRecommendedModules\TabsRecommendedModules;

class TabsRecommendedModulesFactory implements TabsRecommendedModulesFactoryInterface
{
    private $modulesDataProvider;

    /**
     * Constructor.
     *
     * @param ModulesDataProvider $modulesDataProvider
     */
    public function __construct(ModulesDataProvider $modulesDataProvider)
    {
        $this->modulesDataProvider = $modulesDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildFromArray(array $data)
    {
        $tabsRecommendedModules = new TabsRecommendedModules();

        if (empty($data)) {
            return $tabsRecommendedModules;
        }

        $modulesData = $this->modulesDataProvider->getData($this->getModuleNames($data));

        if (empty($modulesData)) {
            return $tabsRecommendedModules;
        }

        foreach ($data as $tabClassName => $tabData) {
            $recommendedModules = new RecommendedModules();

            foreach ($tabData['recommendedModules'] as $position => $moduleName) {
                if (isset($modulesData[$moduleName])) {
                    $recommendedModule = new RecommendedModule(
                        $moduleName,
                        $position,
                        true,
                        $modulesData[$moduleName]
                    );
                    $recommendedModules->addRecommendedModule($recommendedModule);
                }
            }

            $recommendedModules->sortByPosition();

            $tabRecommendedModules = new TabRecommendedModules(
                $tabClassName,
                $tabData['displayMode'],
                $recommendedModules
            );
            $tabsRecommendedModules->addTab($tabRecommendedModules);
        }

        return $tabsRecommendedModules;
    }

    /**
     * @param array $data
     *
     * @return string[]
     */
    private function getModuleNames(array $data)
    {
        $moduleNames = [];

        foreach ($data as $tabClassName => $tabData) {
            foreach ($tabData['recommendedModules'] as $position => $moduleName) {
                $moduleNames[] = $moduleName;
            }
        }

        return array_unique($moduleNames);
    }
}
