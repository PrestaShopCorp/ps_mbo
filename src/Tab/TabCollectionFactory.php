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

namespace PrestaShop\Module\Mbo\Tab;

use PrestaShop\Module\Mbo\Module\Module;
use PrestaShop\Module\Mbo\Module\Repository;

class TabCollectionFactory implements TabCollectionFactoryInterface
{
    /**
     * @var Repository
     */
    protected $moduleRepository;

    public function __construct(Repository $moduleRepository)
    {
        $this->moduleRepository = $moduleRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildFromArray(array $data): TabCollectionInterface
    {
        $tabCollection = new TabCollection();
        if (empty($data)) {
            return $tabCollection;
        }

        $modulesData = $this->getModules($data);

        if (empty($modulesData)) {
            return $tabCollection;
        }

        foreach ($data as $tabClassName => $tabData) {
            $tab = new Tab();
            $tab->setLegacyClassName($tabClassName);
            $tab->setDisplayMode($tabData['displayMode']);

            $tabCollection->addTab($tab);
        }

        return $tabCollection;
    }

    /**
     * @param array $data
     *
     * @return array<string, Module>
     */
    protected function getModules(array $data): array
    {
        $moduleNames = [];

        foreach ($data as $tabData) {
            foreach ($tabData['recommendedModules'] as $moduleName) {
                $moduleNames[$moduleName] = $this->moduleRepository->getModule($moduleName);
            }
        }

        return $moduleNames;
    }
}
