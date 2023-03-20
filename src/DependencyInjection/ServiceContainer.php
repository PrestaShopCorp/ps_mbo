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

namespace PrestaShop\Module\Mbo\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ServiceContainer
{
    /**
     * @var string Module Name
     */
    private $moduleName;

    /**
     * @var string Module Local Path
     */
    private $moduleLocalPath;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        string $moduleName,
        string $moduleLocalPath
    ) {
        $this->moduleName = $moduleName;
        $this->moduleLocalPath = $moduleLocalPath;
    }

    public function getService(string $serviceName): ?object
    {
        if (null === $this->container) {
            $this->initContainer();
        }

        return $this->container->get($serviceName);
    }

    /**
     * Instantiate a new ContainerProvider
     */
    private function initContainer(): void
    {
        $cacheDirectory = new CacheDirectoryProvider(
            _PS_VERSION_,
            _PS_ROOT_DIR_,
            _PS_MODE_DEV_
        );
        $containerProvider = new ContainerProvider($this->moduleName, $this->moduleLocalPath, $cacheDirectory);

        $this->container = $containerProvider->get(defined('_PS_ADMIN_DIR_') || defined('PS_INSTALLATION_IN_PROGRESS') || PHP_SAPI === 'cli' ? 'admin' : 'front');
    }
}
