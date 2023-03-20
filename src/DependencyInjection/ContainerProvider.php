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

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContainerProvider
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
     * @var CacheDirectoryProvider
     */
    private $cacheDirectory;

    public function __construct(
        string $moduleName,
        string $moduleLocalPath,
        CacheDirectoryProvider $cacheDirectory
    ) {
        $this->moduleName = $moduleName;
        $this->moduleLocalPath = $moduleLocalPath;
        $this->cacheDirectory = $cacheDirectory;
    }

    public function get(string $containerName): ContainerInterface
    {
        $containerClassName = ucfirst($this->moduleName)
            . ucfirst($containerName)
            . 'Container'
        ;
        $containerFilePath = $this->cacheDirectory->getPath() . '/' . $containerClassName . '.php';
        $containerConfigCache = new ConfigCache($containerFilePath, _PS_MODE_DEV_);

        if ($containerConfigCache->isFresh()) {
            require_once $containerFilePath;

            return new $containerClassName();
        }

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->set(
            $this->moduleName . '.cache.directory',
            $this->cacheDirectory
        );
        $moduleConfigPath = $this->moduleLocalPath
            . 'config/services/'
        ;
        $fileLocator = new FileLocator($moduleConfigPath);
        $loader = new YamlFileLoader($containerBuilder, $fileLocator);
        $loader->setResolver(new LoaderResolver([
            new PhpFileLoader($containerBuilder, $fileLocator),
            new XmlFileLoader($containerBuilder, $fileLocator),
        ]));

        $loader->load('http_clients.yml');
        $loader->load('distribution.yml');
        $loader->load('accounts.yml');
        $loader->load('handler.yml');
        $loader->load('api/distribution.yml');

        $containerBuilder->compile(true);
        $dumper = new PhpDumper($containerBuilder);
        $containerConfigCache->write(
            $dumper->dump(['class' => $containerClassName]),
            $containerBuilder->getResources()
        );

        return $containerBuilder;
    }
}
