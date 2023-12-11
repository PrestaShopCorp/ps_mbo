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

namespace PrestaShop\Module\Mbo\Service;

use Exception;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\PrestaShop\Core\Cache\Clearer\CacheClearerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class MboSymfonyCacheClearer implements CacheClearerInterface
{
    private $shutdownRegistered = false;

    /**
     * @inheritDoc
     */
    public function clear()
    {
        /* @var \AppKernel */
        global $kernel;
        if (!$kernel) {
            return;
        }

        if ($this->shutdownRegistered) {
            return;
        }

        $this->shutdownRegistered = true;
        register_shutdown_function(function () use($kernel) {
            try {
                foreach (['prod', 'dev'] as $environment) {
                    $cacheDir = _PS_ROOT_DIR_ . '/var/cache/' . $environment . '/';
                    if (file_exists($cacheDir)) {
                        $cache_files = Finder::create()
                            ->in($cacheDir)
                            ->depth('==0');
                        (new Filesystem())->remove($cache_files);
                    }
                }

                // Warmup prod environment only (not needed for dev since many things are dynamic)
                $application = new Application($kernel);
                $application->setAutoExit(false);
                $input = new ArrayInput([
                    'command' => 'cache:warmup',
                    '--no-optional-warmers' => true,
                    '--env' => 'prod',
                    '--no-debug' => true,
                ]);

                $output = new NullOutput();
                $application->doRun($input, $output);
            } catch (Exception $e) {
                // Do nothing but at least does not break the loop nor function
                ErrorHelper::reportError($e);
            } finally {
                \Hook::exec('actionClearSf2Cache');
            }
        });
    }
}
