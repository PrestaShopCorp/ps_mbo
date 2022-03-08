<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace PrestaShop\Module\Mbo\Modules\QueryHandler;

use PrestaShop\Module\Mbo\Modules\CollectionFactory;
use PrestaShop\Module\Mbo\Modules\Filters;
use PrestaShop\Module\Mbo\Modules\FiltersFactory;
use PrestaShop\Module\Mbo\Modules\Query\GetModulesForListing;
use PrestaShop\Module\Mbo\Modules\RepositoryInterface;

/**
 * @internal
 */
final class GetModulesForListingHandler
{
    /**
     * @var FiltersFactory
     */
    private $moduleFiltersFactory;

    /**
     * @var CollectionFactory
     */
    private $moduleCollectionFactory;

    /**
     * @var RepositoryInterface
     */
    private $moduleRepository;

    public function __construct(
        FiltersFactory $moduleFiltersFactory,
        CollectionFactory $moduleCollectionFactory,
        RepositoryInterface $moduleRepository
    ) {
        $this->moduleFiltersFactory = $moduleFiltersFactory;
        $this->moduleCollectionFactory = $moduleCollectionFactory;
        $this->moduleRepository = $moduleRepository;
    }

    public function handle(GetModulesForListing $query)
    {
        $filters = $this->moduleFiltersFactory->create();
        $filters
            ->setType(Filters\Type::MODULE | Filters\Type::SERVICE)
            ->setStatus(Filters\Status::ALL & ~Filters\Status::INSTALLED);

        return $this->moduleCollectionFactory->build(
            $this->moduleRepository->fetchAll(),
            $filters
        );
    }
}
