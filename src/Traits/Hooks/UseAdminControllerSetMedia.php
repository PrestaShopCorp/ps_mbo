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
use Tools;

trait UseAdminControllerSetMedia
{
    /**
     * @return void
     *
     * @throws Exception
     */
    public function bootUseAdminControllerSetMedia(): void
    {
        if (Tools::getValue('controller') === 'AdminPsMboModule') {
            $this->context->controller->addJs($this->getPathUri() . 'views/js/catalog-see-more.js?v=' . $this->version);
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/module-catalog.css?v=' . $this->version);
        }
    }

    /**
     * Hook actionAdminControllerSetMedia.
     */
    public function hookActionAdminControllerSetMedia(): void
    {
        if (empty($this->adminControllerMediaMethods)) {
            return;
        }

        usort($this->adminControllerMediaMethods, function ($a, $b) {
            return $a['order'] === $b['order'] ? 0 : ($a['order'] < $b['order'] ? -1 : 1);
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
}
