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

use PrestaShop\Module\Mbo\Module\Collection;
use PrestaShop\Module\Mbo\Module\Filters;
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

trait UseActionListModules
{
    /**
     * Hook displayModuleConfigureExtraButtons.
     * Add additional buttons on the module configure page's toolbar.
     *
     * @return array<array<string, string>>
     */
    public function hookActionListModules(array $params): array
    {
        $filters = $this->get('mbo.modules.filters.factory')->create();
        $filters
            ->setType(Filters\Type::MODULE | Filters\Type::SERVICE)
            ->setStatus(Filters\Status::ALL & Filters\Status::INSTALLED);

        /**
         * @var Collection $modulesCollection
         */
        $modulesCollection = $this->get('mbo.modules.collection.factory')->build(
            $this->get('mbo.modules.repository')->fetchAll(),
            $filters
        );

        $modules = [];
        /**
         * @var ModuleInterface $module
         */
        foreach ($modulesCollection as $name => $module) {
            $modules[] = [
                'name' => $name,
                'displayName' => $module->get('displayName'),
                'description' => $module->get('description'),
                'additional_description' => $this->getAdditionalDescription((int) $module->get('id'), $name),
                'version' => $module->get('version'),
                'version_available' => $module->get('version_available'),
                'author' => $module->get('author'),
                'download_url' => $module->get('url'),
                'img' => $module->get('img'),
                'tab' => $module->get('tab'),
            ];
        }

        return $modules;
    }

    private function getAdditionalDescription(int $moduleId, string $moduleName)
    {
        return $this->get('twig')->render(
            '@Modules/ps_mbo/views/templates/hook/twig/module_manager_additional_description.html.twig', [
                'module' => [
                    'attributes' => [
                        'id' => $moduleId,
                        'name' => $moduleName,
                    ],
                ],
            ]
        );
    }
}
