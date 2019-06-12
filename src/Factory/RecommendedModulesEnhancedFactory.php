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
use PrestaShop\Module\Mbo\RecommendedModules\RecommendedModuleEnhanced;
use PrestaShop\Module\Mbo\RecommendedModules\RecommendedModulesEnhanced;
use PrestaShop\Module\Mbo\RecommendedModules\RecommendedModulesInterface;

class RecommendedModulesEnhancedFactory implements RecommendedModulesEnhancedFactoryInterface
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
    public function buildFromRecommendedModules(RecommendedModulesInterface $recommendedModules)
    {
        $recommendedModulesEnhanced = new RecommendedModulesEnhanced();

        $modulesData = $this->modulesDataProvider->getData($recommendedModules->getRecommendedModuleNames());

        foreach ($modulesData as $moduleData) {
            if (isset($moduleData['attributes']['name'], $moduleData['database']['installed'])) {
                $recommendedModule = $recommendedModules->getRecommendedModule($moduleData['attributes']['name']);
                if ($recommendedModule) {
                    $recommendedModuleEnhanced = new RecommendedModuleEnhanced(
                        $recommendedModule->getModuleName(),
                        $recommendedModule->getPosition(),
                        (bool) $moduleData['database']['installed'],
                        $moduleData
                    );
                    $recommendedModulesEnhanced->addRecommendedModule($recommendedModuleEnhanced);
                }
            }
        }

        $recommendedModulesEnhanced->sortByPosition();

        return $recommendedModulesEnhanced;
    }
}
