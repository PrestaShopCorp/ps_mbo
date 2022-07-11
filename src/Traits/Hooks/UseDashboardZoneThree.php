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

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use ToolsCore as Tools;

trait UseDashboardZoneThree
{
    /**
     * @return void
     *
     * @throws \Exception
     */
    public function bootUseDashboardZoneThree(): void
    {
        if (method_exists($this, 'addAdminControllerMedia')) {
            $this->addAdminControllerMedia('loadMediaForDashboardColumnThree');
        }
    }

    /**
     * Display addons data & links in the third column of the dashboard
     *
     * @param array $params
     *
     * @return false|string
     */
    public function hookDashboardZoneThree(array $params)
    {
        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        $moduleManager = $moduleManagerBuilder->build();

        $this->smarty->assign(
            [
                'new_version_url' => Tools::getCurrentUrlProtocolPrefix() . _PS_API_DOMAIN_ . '/version/check_version.php?v=' . _PS_VERSION_ . '&lang=' . $this->context->language->iso_code . '&autoupgrade=' . (int) ($moduleManager->isInstalled('autoupgrade') && $moduleManager->isEnabled('autoupgrade')) . '&hosted_mode=' . (int) defined('_PS_HOST_MODE_'),
                'help_center_link' => $this->getHelpCenterLink($this->context->language->iso_code),
            ]
        );

        return $this->display($this->name, 'dashboard-zone-three.tpl');
    }

    /**
     * Returns the Help center link for the provided locale
     *
     * @param string $languageCode 2-letter locale code
     *
     * @return string
     */
    protected function getHelpCenterLink(string $languageCode): string
    {
        $links = [
            'fr' => 'https://www.prestashop.com/fr/contact?utm_source=back-office&utm_medium=links&utm_campaign=help-center-fr&utm_content=download17',
            'en' => 'https://www.prestashop.com/en/contact?utm_source=back-office&utm_medium=links&utm_campaign=help-center-en&utm_content=download17',
            'es' => 'https://www.prestashop.com/es/contacto?utm_source=back-office&utm_medium=links&utm_campaign=help-center-es&utm_content=download17',
            'de' => 'https://www.prestashop.com/de/kontakt?utm_source=back-office&utm_medium=links&utm_campaign=help-center-de&utm_content=download17',
            'it' => 'https://www.prestashop.com/it/contatti?utm_source=back-office&utm_medium=links&utm_campaign=help-center-it&utm_content=download17',
            'nl' => 'https://www.prestashop.com/nl/contacteer-ons?utm_source=back-office&utm_medium=links&utm_campaign=help-center-nl&utm_content=download17',
            'pt' => 'https://www.prestashop.com/pt/contato?utm_source=back-office&utm_medium=links&utm_campaign=help-center-pt&utm_content=download17',
            'pl' => 'https://www.prestashop.com/pl/kontakt?utm_source=back-office&utm_medium=links&utm_campaign=help-center-pl&utm_content=download17',
        ];

        return $links[$languageCode] ?? $links['en'];
    }

    /**
     * Add JS and CSS file
     *
     * @return void
     *
     * @see \PrestaShop\Module\Mbo\Traits\Hooks\UseActionAdminControllerSetMedia
     */
    protected function loadMediaForDashboardColumnThree(): void
    {
        if (Tools::getValue('controller') === 'AdminDashboard') {
            $this->context->controller->addJs($this->getPathUri() . 'views/js/dashboard-news.js?v=' . $this->version);
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/dashboard.css');
        }
    }
}
