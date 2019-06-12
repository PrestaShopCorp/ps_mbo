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

namespace PrestaShop\Module\Mbo\RecommendedModules;

use Iterator;
use FilterIterator;

class RecommendedModulesFilter extends FilterIterator
{
    /**
     * @var bool
     */
    private $isInstalled;

    /**
     * Constructor.
     *
     * @param Iterator $iterator
     * @param bool $isInstalled
     */
    public function __construct(Iterator $iterator, $isInstalled)
    {
        parent::__construct($iterator);
        $this->isInstalled = $isInstalled;
    }

    /**
     * {@inheritdoc}
     */
    public function accept()
    {
        /**
         * @var RecommendedModuleInterface
         */
        $recommendedModules = $this->getInnerIterator()->current();

        return $this->isInstalled === $recommendedModules->isInstalled();
    }
}
