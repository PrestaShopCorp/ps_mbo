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

namespace PrestaShop\Module\Mbo\Module\SourceRetriever;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipArchive;

class UrlSourceRetriever implements SourceRetrieverInterface
{
    private const MODULE_REGEX = '/^(.*)\/\1\.php$/i';

    private const AUTHORIZED_MIME = [
        'application/zip',
        'application/x-gzip',
        'application/gzip',
        'application/x-gtar',
        'application/x-tgz',
    ];

    /**
     * @var string
     */
    public $cacheDir;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @throws Exception
     */
    public function validate(string $zipFileName, string $moduleName): bool
    {
        if ($this->isZipFile($zipFileName)) {
            $zip = new ZipArchive();
            if ($zip->open($zipFileName) === true) {
                for ($i = 0; $i < $zip->numFiles; ++$i) {
                    if (preg_match(self::MODULE_REGEX, $zip->getNameIndex($i), $matches)) {
                        $zip->close();

                        $zipModuleName = $matches[1];

                        return $zipModuleName === $moduleName;
                    }
                }
                $zip->close();
            }
        }

        throw new ModuleErrorException($this->translator->trans('This file does not seem to be a valid module zip', [], 'Admin.Modules.Notification'));
    }

    public function get($source): string
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
        $client->request('GET', $source, ['sink' => $stream]);

        return $temporaryZipFilename;
    }

    private function isZipFile(string $file)
    {
        return is_file($file) && in_array(mime_content_type($file), self::AUTHORIZED_MIME);
    }
}
