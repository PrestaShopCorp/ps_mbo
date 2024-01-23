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

use GuzzleHttp\Exception\GuzzleException;
use PrestaShop\Module\Mbo\Module\SourceRetriever\AddonsUrlSourceRetriever;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\SourceHandlerInterface;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\ZipSourceHandler;

class AddonsUrlSourceHandler implements SourceHandlerInterface
{
    private const URL_VALIDATION_REGEX = "/^https?:\\/\\/(?:www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{0,256}api-addons\\.prestashop\\.com(?:[-a-zA-Z0-9()@:%_\\+.~#?&\\/=]*)$/";

    private const ZIP_FILENAME_PATTERN = '/(\w+)\.zip\b/';

    /**
     * @var ZipSourceHandler
     */
    private $zipSourceHandler;

    /**
     * @var AddonsUrlSourceRetriever
     */
    private $addonsUrlSourceRetriever;

    public function __construct(
        AddonsUrlSourceRetriever $addonsUrlSourceRetriever,
        ZipSourceHandler $zipSourceHandler
    ) {
        $this->zipSourceHandler = $zipSourceHandler;
        $this->addonsUrlSourceRetriever = $addonsUrlSourceRetriever;
    }

    /**
     * @throws GuzzleException
     */
    public function canHandle($source): bool
    {
        return $this->addonsUrlSourceRetriever->assertCanBeDownloaded($source);
    }

    public function getModuleName($source): ?string
    {
        return $this->addonsUrlSourceRetriever->getModuleName($source);
    }

    public function handle(string $source): void
    {
        $path = $this->addonsUrlSourceRetriever->get($source);
        $this->zipSourceHandler->handle($path);
        @unlink($path);
    }
}
