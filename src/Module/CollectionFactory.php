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

namespace PrestaShop\Module\Mbo\Module;

use PrestaShop\PrestaShop\Adapter\Module\Module;

/**
 * Builds a modules' collection formatted and filtered
 */
class CollectionFactory
{
    public function build(array $modules, ?FiltersInterface $filters): Collection
    {
        return new Collection(
            $this->filter($modules, $filters)
        );
    }

    /**
     * @return array<Module> retrieve a list of addons, regarding the $filters used
     */
    protected function filter(array $modules, ?FiltersInterface $filters = null): array
    {
        $result = [];
        foreach ($modules as $key => $module) {
            // Part One : Removing addons not related to the selected product type
            if ($module->get('product_type') == 'module') {
                $productType = Filters\Type::MODULE;
            } elseif ($module->get('product_type') == 'service') {
                $productType = Filters\Type::SERVICE;
            }

            if (!isset($productType) || !$filters->hasType($productType)) {
                continue;
            }

            // Part Two : Remove module not installed if specified
            if (!$filters->hasStatus(Filters\Status::ALL)) {
                if ($module->database->get('installed') == 1
                    && (
                        $filters->hasStatus(~Filters\Status::INSTALLED)
                        || !$filters->hasStatus(Filters\Status::INSTALLED)
                    )
                ) {
                    unset($modules[$key]);

                    continue;
                }

                if ($module->database->get('installed') == 0
                    && (
                        !$filters->hasStatus(~Filters\Status::INSTALLED)
                        || $filters->hasStatus(Filters\Status::INSTALLED)
                    )
                ) {
                    unset($modules[$key]);

                    continue;
                }

                if ($module->database->get('installed') == 1
                    && $module->database->get('active') == 1
                    && !$filters->hasStatus(~Filters\Status::ENABLED)
                    && $filters->hasStatus(Filters\Status::ENABLED)
                ) {
                    unset($modules[$key]);

                    continue;
                }

                if ($module->database->get('installed') == 1
                    && $module->database->get('active') == 0
                    && !$filters->hasStatus(Filters\Status::ENABLED)
                    && $filters->hasStatus(~Filters\Status::ENABLED)
                ) {
                    unset($modules[$key]);

                    continue;
                }
            }

            $result[$key] = $module;
        }

        return $modules;
    }
}
