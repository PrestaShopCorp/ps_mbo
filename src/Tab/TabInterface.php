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

namespace PrestaShop\Module\Mbo\Tab;

interface TabInterface
{
    /**
     * @var string
     */
    public const RECOMMENDED_BUTTON_TYPE = 'button';

    /**
     * @var string
     */
    public const RECOMMENDED_AFTER_CONTENT_TYPE = 'after_content';

    /**
     * @var string[]
     */
    public const TABS_WITH_RECOMMENDED_MODULES_BUTTON = [
        'AdminOrders',
        'AdminInvoices',
        'AdminSlip',
        'AdminDeliverySlip',
        'AdminProducts',
        'AdminFeatures',
        'AdminManufacturers',
        'AdminCartRules',
        'AdminSpecificPriceRule',
        'AdminCustomers',
        'AdminCustomerThreads',
        'AdminStats',
        'AdminCmsContent',
        'AdminImages',
        'AdminShipping',
        'AdminStatuses', // Shop Parameters > Order Settings > Statuses
        'AdminGroups', // Shop Parameters > Customer Settings > Groups
        'AdminContacts', // Shop Parameters > Contact > Contact
        'AdminMeta', // Shop Parameters > Traffic & SEO > SEO & URLs
        'AdminSearchConf', // Shop Parameters > Search > Search
        'AdminAdminPreferences', // Advanced Parameters > Administration
        'AdminEmails', // Advanced Parameters > E-mail
    ];

    /**
     * @var string[]
     */
    public const TABS_WITH_RECOMMENDED_MODULES_AFTER_CONTENT = [
        'AdminMarketing',
        'AdminPayment',
        'AdminCarriers',
    ];

    /**
     * Get the class name of the tab.
     *
     * @return string
     */
    public function getLegacyClassName(): string;

    /**
     * @param string $legacyClassName
     *
     * @return TabInterface
     */
    public function setLegacyClassName(string $legacyClassName): TabInterface;

    /**
     * Get the display mode of the tab.
     *
     * @return string
     */
    public function getDisplayMode(): string;

    /**
     * @param string $displayMode
     *
     * @return TabInterface
     */
    public function setDisplayMode(string $displayMode): TabInterface;

    /**
     * @return bool
     */
    public function shouldDisplayAfterContent(): bool;

    /**
     * @param string $controllerName
     *
     * @return bool
     */
    public static function mayDisplayRecommendedModules(string $controllerName): bool;
}
