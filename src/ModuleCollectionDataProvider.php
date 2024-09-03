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

namespace PrestaShop\Module\Mbo;

use Module;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Adapter\Module\AdminModuleDataProvider;
use PrestaShop\PrestaShop\Adapter\Presenter\PresenterInterface;
use PrestaShop\PrestaShop\Core\Addon\AddonsCollection;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepository;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepositoryInterface;
use PrestaShopBundle\Entity\Repository\TabRepository;
use Profile;

class ModuleCollectionDataProvider
{
    /**
     * @var AdminModuleDataProvider
     */
    private $addonsProvider;

    /**
     * @var ModuleRepositoryInterface
     */
    private $moduleRepository;

    /**
     * @var PresenterInterface
     */
    private $modulePresenter;

    /**
     * @var TabRepository
     */
    private $tabRepository;

    /**
     * @var LegacyContext
     */
    private $context;

    /**
     * Constructor.
     *
     * @param AdminModuleDataProvider $addonsProvider
     * @param ModuleRepositoryInterface $moduleRepository
     * @param PresenterInterface $modulePresenter
     * @param TabRepository $tabRepository
     * @param LegacyContext $context
     */
    public function __construct(
        AdminModuleDataProvider $addonsProvider,
        ModuleRepositoryInterface $moduleRepository,
        PresenterInterface $modulePresenter,
        TabRepository $tabRepository,
        LegacyContext $context
    ) {
        $this->addonsProvider = $addonsProvider;
        $this->moduleRepository = $moduleRepository;
        $this->modulePresenter = $modulePresenter;
        $this->tabRepository = $tabRepository;
        $this->context = $context;
    }

    /**
     * @param array $moduleNames
     *
     * @return array
     */
    public function getData(array $moduleNames)
    {
        $data = [];

        $modulesOnDisk = AddonsCollection::createFrom($this->moduleRepository->getList());
        $modulesOnDisk = $this->addonsProvider->generateAddonsUrls($modulesOnDisk);

        foreach ($modulesOnDisk as $module) {
            /** @var \PrestaShop\PrestaShop\Adapter\Module\Module $module */
            if (!in_array($module->get('name'), $moduleNames)) {
                continue;
            }

            if ($module->get('id')) {
                $isEmployeeAllowed = (bool) Module::getPermissionStatic(
                    $module->get('id'),
                    'configure',
                    $this->context->getContext()->employee
                );
            } else {
                $ModuleTabId = $this->tabRepository->findOneIdByClassName('AdminModules');
                /** @var array $access */
                $access = Profile::getProfileAccess(
                    $this->context->getContext()->employee->id_profile,
                    $ModuleTabId
                );

                $isEmployeeAllowed = !$access['edit'];
            }

            if (false === $isEmployeeAllowed) {
                continue;
            }

            if ($module->get('author') === ModuleRepository::PARTNER_AUTHOR) {
                $module->set('type', 'addonsPartner');
            }

            if (!empty($module->get('description'))) {
                $module->set('description', html_entity_decode($module->get('description'), ENT_QUOTES));
            }

            $module->fillLogo();
            $data[$module->get('name')] = $this->modulePresenter->present($module);
        }

        return $data;
    }
}
