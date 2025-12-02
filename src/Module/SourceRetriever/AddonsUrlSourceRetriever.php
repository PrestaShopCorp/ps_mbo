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

use PrestaShop\Module\Mbo\Addons\Provider\AddonsDataProvider;
use PrestaShop\Module\Mbo\Exception\AddonsDownloadModuleException;
use PrestaShop\Module\Mbo\Exception\ClientRequestException;
use PrestaShop\Module\Mbo\Exception\FileOperationException;
use PrestaShop\Module\Mbo\Helpers\AddonsApiHelper;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Helpers\ModuleErrorHelper;
use PrestaShop\Module\Mbo\Module\Exception\SourceNotCheckedException;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddonsUrlSourceRetriever implements SourceRetrieverInterface
{
    private const URL_VALIDATION_REGEX = '/^https?:\\/\\/(?:www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{0,256}api-addons\\.prestashop\\.com(?:[-a-zA-Z0-9()@:%_\\+.~#?&\\/=]*)$/';

    private const MODULE_REGEX = '/^(.*)\/\1\.php$/i';

    private const ZIP_FILENAME_PATTERN = '/(\w+)\.zip\b/';

    private const AUTHORIZED_MIME = [
        'application/zip',
        'application/x-gzip',
        'application/gzip',
        'application/x-gtar',
        'application/x-tgz',
    ];

    /**
     * @var ?string
     */
    private $moduleName;

    /**
     * @var string
     */
    public $cacheDir;

    /**
     * @var AddonsDataProvider
     */
    private $addonsDataProvider;

    /**
     * @var mixed
     */
    private $handledSource;

    /**
     * @var string
     */
    private $shopUrl;

    /**
     * @var array
     */
    private $headers;
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var RequestFactoryInterface
     */
    protected $requestFactory;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        AddonsDataProvider $addonsDataProvider,
        TranslatorInterface $translator,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        ConfigurationInterface $configuration
    ) {
        $this->addonsDataProvider = $addonsDataProvider;
        $this->translator = $translator;
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->shopUrl = $configuration->get('_PS_BASE_URL_');
        $this->headers = $this->buildHeaders();
    }

    public function assertCanBeDownloaded($source): bool
    {
        if (!self::assertIsAddonsUrl($source)) {
            return false;
        }

        $authenticatedQueryParameters = [];
        try {
            $source = $this->computeAddonsRequestUrl($source);
            $response = $this->requestFromUrl('HEAD', $source, $this->headers);
        } catch (ClientException $e) {
            throw ModuleErrorHelper::reportAndConvertError(new AddonsDownloadModuleException($e, $authenticatedQueryParameters), $authenticatedQueryParameters);
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);

            return false;
        }

        $this->moduleName = null;

        if (preg_match(self::ZIP_FILENAME_PATTERN, $source, $moduleName) === 1) {
            $this->moduleName = $moduleName[1];
        }

        $headers = $response->getHeaders();

        if (isset($headers['content-disposition'])
            && preg_match(self::ZIP_FILENAME_PATTERN, reset($headers['content-disposition']), $moduleName) === 1
        ) {
            $this->moduleName = $moduleName[1];
        }

        if (!empty($this->moduleName)
            && $response->getStatusCode() === 200
            && isset($headers['content-type'])
            && reset($headers['content-type']) === 'application/zip'
        ) {
            $this->handledSource = $source;

            return true;
        }

        return false;
    }

    public function getModuleName($source): ?string
    {
        $this->assertSourceHasBeenChecked($source);

        return $this->moduleName;
    }

    public function get($source, ?string $expectedModuleName = null, ?array $options = []): string
    {
        $this->assertSourceHasBeenChecked($source);

        // First save the file to filesystem
        $temporaryFilename = tempnam($this->cacheDir, 'mod');
        if (false === $temporaryFilename) {
            throw new FileOperationException('Failed to create temporary file to store downloaded source');
        }

        $temporaryZipFilename = $temporaryFilename . '.zip';
        rename($temporaryFilename, $temporaryZipFilename);
        $fileHandle = fopen($temporaryZipFilename, 'wb');
        if (false === $fileHandle) {
            throw new FileOperationException('Failed to open temporary file to store downloaded source');
        }

        try {
            $stream = $this->requestFromUrl(
                'GET',
                $this->handledSource,
                $this->headers
            )->getBody();

            while (!$stream->eof()) {
                fwrite($fileHandle, $stream->read(8192));
            }
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);
            throw $e;
        } finally {
            fclose($fileHandle);
        }

        if (null !== $expectedModuleName) {
            $this->validate($temporaryZipFilename, $expectedModuleName);
        }

        return $temporaryZipFilename;
    }

    /**
     * @throws \Exception
     */
    public function validate(string $zipFileName, string $expectedModuleName): bool
    {
        if (!$this->isZipFile($zipFileName)) {
            throw new ModuleErrorException($this->translator->trans('This file does not seem to be a valid module zip', [], 'Admin.Modules.Notification'));
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipFileName) === true) {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                if (preg_match(self::MODULE_REGEX, $zip->getNameIndex($i), $matches)) {
                    $zip->close();

                    $zipModuleName = $matches[1];

                    return $zipModuleName === $expectedModuleName;
                }
            }
            $zip->close();
        }

        throw new ModuleErrorException($this->translator->trans('Downloaded zip file does not contain the expected module', [], 'Admin.Modules.Notification'));
    }

    public static function assertIsAddonsUrl($source): bool
    {
        return is_string($source) && 1 === preg_match(self::URL_VALIDATION_REGEX, $source);
    }

    private function assertSourceHasBeenChecked($source): void
    {
        if ($this->computeAddonsRequestUrl($source) !== $this->handledSource) {
            throw new SourceNotCheckedException('Method assertCanBeDownloaded() should be called first');
        }
    }

    private function computeAddonsRequestUrl(string $source): string
    {
        $url_parts = parse_url($source);
        if (is_array($url_parts) && isset($url_parts['query'])) {
            parse_str($url_parts['query'], $params);
        } else {
            $params = [];
        }
        $params['shop_url'] = $this->shopUrl;

        $url_parts['query'] = urldecode(http_build_query($params));

        return http_build_url($url_parts);
    }

    private function isZipFile(string $file): bool
    {
        return is_file($file) && in_array(mime_content_type($file), self::AUTHORIZED_MIME);
    }

    private function requestFromUrl(string $method, string $url, array $headers): ResponseInterface
    {
        $request = $this->requestFactory->createRequest($method, $url);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new ClientRequestException($response->getReasonPhrase(), $response->getStatusCode());
        }

        return $response;
    }

    private function buildHeaders(): array
    {
        $headers = [];
        $authToken = $this->addonsDataProvider->getAuthenticationToken();
        if ($authToken) {
            $headers['Authorization'] = 'Bearer ' . $authToken;
        }

        $accountsShopUuid = $this->addonsDataProvider->getAccountsShopUuid();
        if (!empty($accountsShopUuid)) {
            $headers['accounts_shop_uuid'] = $accountsShopUuid;
        }

        return array_merge($headers, AddonsApiHelper::addCustomHeaders());
    }
}
