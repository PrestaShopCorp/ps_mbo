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

namespace PrestaShop\Module\Mbo\Module;

use Doctrine\Common\Cache\CacheProvider;
use Exception;
use ParseError;
use Module as LegacyModule;
use PrestaShop\Module\Mbo\Addons\ListFilter;
use PrestaShop\Module\Mbo\Addons\ListFilterOrigin;
use PrestaShop\Module\Mbo\Addons\ListFilterStatus;
use PrestaShop\Module\Mbo\Addons\ListFilterType;
use PrestaShop\PrestaShop\Adapter\Module\Module;
use PrestaShop\PrestaShop\Adapter\Module\ModuleDataUpdater;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\DoctrineProvider;
use Symfony\Component\Translation\TranslatorInterface;

class CollectionFactory
{
    public function build(array $modules, FilterInterface $filters, CategoryRepositoryInterface $categories)
    {
        $collection = new Collection();
    }

    /**
     * @param Filter $filter
     *
     * @return array<Module> retrieve a list of addons, regarding the $filter used
     */
    public function filter(array $modules, Filter $filter)
    {
        /** @var Module[] $modules */
        $modules = $this->getList();

        foreach ($modules as $key => &$module) {
            // Part One : Removing addons not related to the selected product type
            if ($filter->type != ListFilterType::ALL) {
                if ($module->attributes->get('productType') == 'module') {
                    $productType = ListFilterType::MODULE;
                }
                if ($module->attributes->get('productType') == 'service') {
                    $productType = ListFilterType::SERVICE;
                }
                if (!isset($productType) || $productType & ~$filter->type) {
                    unset($modules[$key]);

                    continue;
                }
            }

            // Part Two : Remove module not installed if specified
            if ($filter->status != ListFilterStatus::ALL) {
                if ($module->database->get('installed') == 1
                    && ($filter->hasStatus(ListFilterStatus::UNINSTALLED)
                        || !$filter->hasStatus(ListFilterStatus::INSTALLED))) {
                    unset($modules[$key]);

                    continue;
                }

                if ($module->database->get('installed') == 0
                    && (!$filter->hasStatus(ListFilterStatus::UNINSTALLED)
                        || $filter->hasStatus(ListFilterStatus::INSTALLED))) {
                    unset($modules[$key]);

                    continue;
                }

                if ($module->database->get('installed') == 1
                    && $module->database->get('active') == 1
                    && !$filter->hasStatus(ListFilterStatus::DISABLED)
                    && $filter->hasStatus(ListFilterStatus::ENABLED)) {
                    unset($modules[$key]);

                    continue;
                }

                if ($module->database->get('installed') == 1
                    && $module->database->get('active') == 0
                    && !$filter->hasStatus(ListFilterStatus::ENABLED)
                    && $filter->hasStatus(ListFilterStatus::DISABLED)) {
                    unset($modules[$key]);

                    continue;
                }
            }

            // Part Three : Remove addons not related to the proper source (ex Addons)
            if ($filter->origin != ListFilterOrigin::ALL) {
                if (!$module->attributes->has('origin_filter_value') &&
                    !$filter->hasOrigin(ListFilterOrigin::DISK)
                ) {
                    unset($modules[$key]);

                    continue;
                }
                if ($module->attributes->has('origin_filter_value') &&
                    !$filter->hasOrigin($module->attributes->get('origin_filter_value'))
                ) {
                    unset($modules[$key]);

                    continue;
                }
            }
        }

        return $modules;
    }
}
