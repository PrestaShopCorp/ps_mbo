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

use Exception;
use PrestaShop\Module\Mbo\Tab\Tab;
use Tools;

trait UseActionAdminControllerSetMedia
{
    /**
     * @return void
     *
     * @throws Exception
     */
    public function bootUseActionAdminControllerSetMedia(): void
    {
        if (method_exists($this, 'addAdminControllerMedia')) {
            $this->addAdminControllerMedia('loadMediaForAdminControllerSetMedia', 0);
        }
    }

    /**
     * Hook actionAdminControllerSetMedia.
     */
    public function hookActionAdminControllerSetMedia(): void
    {
        if (Tools::getValue('controller') === "AdminPsMboModule") {
            $this->context->controller->addJs(
                sprintf('%sviews/js/upload_module_with_cdc.js?v=%s', $this->getPathUri(), $this->version)
            );
        }

        if (empty($this->adminControllerMediaMethods)) {
            return;
        }

        usort($this->adminControllerMediaMethods, function ($a, $b) {
            $order = $a['order'] < $b['order'] ? -1 : 1;

            return $a['order'] === $b['order'] ? 0 : $order;
        });
        foreach ($this->adminControllerMediaMethods as $setMediaMethod) {
            $this->{$setMediaMethod['method']}();
        }
    }

    /**
     * @param string $setMediaMethod The method to be called in the setMediaHook
     * @param int $order To ensure that a script is loaded before or after another one
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function addAdminControllerMedia(string $setMediaMethod, int $order = 1): void
    {
        if (!method_exists($this, $setMediaMethod)) {
            throw new Exception("Method '{$setMediaMethod}' is not defined.");
        }
        $this->adminControllerMediaMethods[] = [
            'method' => $setMediaMethod,
            'order' => $order,
        ];
    }

    /**
     * Add JS and CSS file
     *
     * @return void
     */
    protected function loadMediaForAdminControllerSetMedia(): void
    {
        if (in_array(Tools::getValue('controller'), self::CONTROLLERS_WITH_CDC_SCRIPT)) {
            $this->context->controller->addJs('/js/jquery/plugins/growl/jquery.growl.js?v=' . $this->version);
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/module-catalog.css');
        }
        if (in_array(Tools::getValue('controller'), self::CONTROLLERS_WITH_CONNECTION_TOOLBAR)) {
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/connection-toolbar.css');
            $this->context->controller->addJS($this->getPathUri() . 'views/js/connection-toolbar.js');
        }
        if ('AdminPsMboModule' === Tools::getValue('controller')) {
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/hide-toolbar.css');
        }
        if ($this->isAdminLegacyContext()) {
            // Add it to have all script work on all pages...
            $this->context->controller->addJs('/admin-dev/themes/default/js/bundle/default.js?v=' . _PS_VERSION_);
        }
        $this->loadCdcMedia();
    }

    private function loadCdcMedia(): void
    {
        $controllerName = Tools::getValue('controller');
        if(!is_string($controllerName)) {
            return;
        }
        if (
            !Tab::mayDisplayRecommendedModules($controllerName) &&
            !in_array($controllerName, self::CONTROLLERS_WITH_CDC_SCRIPT)
        ) {
            return;
        }

        $this->context->controller->addJs($this->getPathUri() . 'views/js/cdc-error-templating.js');
        $this->context->controller->addCss($this->getPathUri() . 'views/css/cdc-error-templating.css');

        $cdcJsFile = getenv('MBO_CDC_URL');
        if (false === $cdcJsFile || !is_string($cdcJsFile) || empty($cdcJsFile)) {
            $this->context->controller->addJs($this->getPathUri() . 'views/js/cdc-error.js');

            return;
        }

        $this->context->controller->addJs($cdcJsFile);
    }
}
