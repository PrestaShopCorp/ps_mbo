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

namespace PrestaShop\Module\Mbo\Module\SourceHandler;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\SourceHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipArchive;

class UrlSourceHandler implements SourceHandlerInterface
{
    /**
     * @var string
     */
    public $cacheDir;

    /** @var string */
    protected $modulePath;

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(string $modulePath, TranslatorInterface $translator)
    {
        $this->modulePath = rtrim($modulePath, '/') . '/';
        $this->translator = $translator;
    }

    public function canHandle($source): bool
    {
        return is_string($source) && preg_match("#(\b(https?|ftp|file)://)?[-A-Za-z0-9+&@\#/%?=~_|!:,.;]+[-A-Za-z0-9+&@\#/%=~_|]#", $source);
    }

    /**
     * @throws Exception
     */
    public function getModuleName($source): ?string
    {
        throw new Exception('Method name cannot be guessed from URL');
    }

    /**
     * @throws ModuleErrorException
     */
    public function handle(string $source): void
    {
        try {
            $this->handleZipSource(
                $this->downloadSource($source)
            );
        } catch(Exception $e) {
            throw new ModuleErrorException(
                $this->translator->trans(
                    'Cannot extract module in %path%. %error%',
                    [
                        '%path%' => $this->modulePath,
                        '%error%' => $e->getMessage(), // Since php 8.0 getStatusString cannot return false nor a warning
                    ],
                    'Admin.Modules.Notification'
                )
            );
        }
    }

    private function downloadSource(string $url): string
    {
        $client = new Client([
            'timeout' => '7200',
            'CURLOPT_FORBID_REUSE' => true,
            'CURLOPT_FRESH_CONNECT' => true,
        ]);

        // First save the file to filesystem
        $temporaryFilename = tempnam($this->cacheDir, 'mod');
        if (false === $temporaryFilename) {
            throw new Exception('Failed to create temporary file to store downloaded source');
        }

        $temporaryZipFilename = $temporaryFilename . '.zip';
        rename($temporaryFilename, $temporaryZipFilename);

        $resource = fopen($temporaryZipFilename, 'w');
        $stream = Utils::streamFor($resource);
        $client->request('GET', $url, ['sink' => $stream]);

        return $temporaryZipFilename;
    }

    private function handleZipSource(string $source): void
    {
        $zip = new ZipArchive();
        if ($zip->open($source) !== true || !$zip->extractTo($this->modulePath) || !$zip->close()) {
            throw new ModuleErrorException(
                $this->translator->trans(
                    'Cannot extract module in %path%. %error%',
                    [
                        '%path%' => $this->modulePath,
                        '%error%' => @$zip->getStatusString() ?: '', // Since php 8.0 getStatusString cannot return false nor a warning
                    ],
                    'Admin.Modules.Notification'
                )
            );
        }
    }
}