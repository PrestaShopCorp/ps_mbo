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
use PrestaShop\Module\Mbo\Addons\AddonsCollection;
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

    }
}
