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

namespace PrestaShop\Module\Mbo\Service\View;

class InstalledModule
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string|null
     */
    private $configUrl;
    /**
     * @var array
     */
    private $actionUrls;

    public function __construct(
        int $id,
        string $name,
        string $status,
        string $version,
        array $actionUrls,
        string $configUrl = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->status = $status;
        $this->version = $version;
        $this->actionUrls = $actionUrls;
        $this->configUrl = $configUrl;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'version' => $this->version,
            'config_url' => $this->configUrl,
            'module_actions_urls' => $this->actionUrls,
        ];
    }
}
