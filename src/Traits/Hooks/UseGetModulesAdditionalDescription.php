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

use PrestaShop\Module\Mbo\Module\Module;
use PrestaShop\PrestaShop\Core\Module\ModuleCollection;
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

trait UseGetModulesAdditionalDescription
{
    /**
     * @return void
     *
     * @throws \Exception
     */
    public function bootUseGetModulesAdditionalDescription(): void
    {
        if (method_exists($this, 'addAdminControllerMedia')) {
            $this->addAdminControllerMedia('loadMediaForModuleAdditionalDescription');
        }
    }

    /**
     * Hook actionGetModulesAdditionalDescription.
     * Returns an array of additional descriptions to display in front of the module in Module Manager page.
     *
     * @return array
     */
    public function hookActionGetModulesAdditionalDescription(array $params): array
    {
        $modules = $params['module_collection'];

        if (!$modules instanceof ModuleCollection) {
            return [];
        }

        $modulesAdditionalDescriptions = [];

        /** @var ModuleInterface $module */
        foreach ($modules as $module) {
            if (empty($module->get('name'))) {
                continue;
            }

            $moduleName = $module->get('name');

            /** @var Module $addonsModule */
            $addonsModule = $this->get('mbo.modules.repository')->getModule($moduleName);

            if (null === $addonsModule) {
                continue; // Unknown by addons
            }

            $modulesAdditionalDescriptions[$moduleName] = $this->get('twig')->render(
                '@Modules/ps_mbo/views/templates/hook/twig/module_manager_additional_description.html.twig', [
                    'module' => [
                        'attributes' => [
                            'id' => $addonsModule->get('id'),
                            'name' => $moduleName,
                        ],
                    ],
                ]
            );
        }

        return $modulesAdditionalDescriptions;
    }

    /**
     * Add JS and CSS file
     *
     * @see \PrestaShop\Module\Mbo\Traits\Hooks\UseAdminControllerSetMedia
     *
     * @return void
     */
    protected function loadMediaForModuleAdditionalDescription(): void
    {
        if (\Tools::getValue('controller') === 'AdminModulesManage') {
            $this->context->controller->addJs($this->getPathUri() . 'views/js/catalog-see-more.js?v=' . $this->version);
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/module-catalog.css');
        }
    }
}
