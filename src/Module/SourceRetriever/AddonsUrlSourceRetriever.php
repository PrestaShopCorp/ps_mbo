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
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use PrestaShop\Module\Mbo\Addons\Provider\AddonsDataProvider;
use PrestaShop\Module\Mbo\Exception\AddonsDownloadModuleException;
use PrestaShop\Module\Mbo\Helpers\AddonsApiHelper;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Helpers\ModuleErrorHelper;
use PrestaShop\Module\Mbo\Module\Exception\SourceNotCheckedException;
use PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipArchive;

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
     * @var string
     */
    private $modulePath;

    /**
     * @var AddonsDataProvider
     */
    private $addonsDataProvider;

    /**
     * @var mixed
     */
    private $handledSource;

    /**
     * @var mixed
     */
    private $handledSourceCredentials;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        AddonsDataProvider $addonsDataProvider,
        TranslatorInterface $translator,
        string $modulePath
    ) {
        $this->addonsDataProvider = $addonsDataProvider;
        $this->translator = $translator;
        $this->modulePath = rtrim($modulePath, '/') . '/';

        $this->httpClient = new Client([
            'timeout' => '7200',
            'CURLOPT_FORBID_REUSE' => true,
            'CURLOPT_FRESH_CONNECT' => true,
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function assertCanBeDownloaded($source): bool
    {
        if (!self::assertIsAddonsUrl($source)) {
            return false;
        }

        try {
            $authenticatedQueryParameters = $this->computeAuthentication($source);
            $source = $authenticatedQueryParameters['source'];
            $options = $authenticatedQueryParameters['options'] ?? [];

            if (!is_array($options['headers'])) {
                $options['headers'] = [];
            }
            $options['headers'] = array_merge($options['headers'], AddonsApiHelper::addCustomHeaderIfNeeded());

            $response = $this->httpClient->request('HEAD', $source, $options);
        } catch (TransportExceptionInterface|\Exception $e) {
            if ($e instanceof ClientException) {
                try {
                    $this->httpClient->request(
                        'GET',
                        $source,
                        $options
                    );
                } catch (ClientException $clientException) {
                    throw ModuleErrorHelper::reportAndConvertError(
                        new AddonsDownloadModuleException($clientException, $authenticatedQueryParameters ?? []),
                        $authenticatedQueryParameters ?? []
                    );
                }
            }

            ErrorHelper::reportError($e);

            return false;
        }

        $this->moduleName = null;

        if (preg_match(self::ZIP_FILENAME_PATTERN, $source, $moduleName) === 1) {
            $this->moduleName = $moduleName[1];
        }

        $headers = $response->getHeaders();

        if (isset($headers['Content-Disposition'])
            && preg_match(self::ZIP_FILENAME_PATTERN, reset($headers['Content-Disposition']), $moduleName) === 1
        ) {
            $this->moduleName = $moduleName[1];
        }

        if (!empty($this->moduleName)
            && $response->getStatusCode() === 200
            && isset($headers['Content-Type'])
            && reset($headers['Content-Type']) === 'application/zip'
        ) {
            $this->handledSource = $source;
            $this->handledSourceCredentials = $options;

            return true;
        }

        return false;
    }

    public function getModuleName($source): ?string
    {
        $this->assertSourceHasBeenChecked($source);

        return $this->moduleName;
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function get($source, ?string $expectedModuleName = null, ?array $options = []): string
    {
        $this->assertSourceHasBeenChecked($source);

        // First save the file to filesystem
        $temporaryFilename = tempnam($this->cacheDir, 'mod');
        if (false === $temporaryFilename) {
            throw new Exception('Failed to create temporary file to store downloaded source');
        }

        $temporaryZipFilename = $temporaryFilename . '.zip';
        rename($temporaryFilename, $temporaryZipFilename);

        $resource = fopen($temporaryZipFilename, 'w');
        $stream = Utils::streamFor($resource);
        $this->httpClient->request(
            'GET',
            $this->handledSource,
            array_merge(['sink' => $stream], $this->handledSourceCredentials, $options)
        );

        if (null !== $expectedModuleName) {
            $this->validate($temporaryZipFilename, $expectedModuleName);
        }

        return $temporaryZipFilename;
    }

    /**
     * @throws Exception
     */
    public function validate(string $zipFileName, string $expectedModuleName): bool
    {
        if (!$this->isZipFile($zipFileName)) {
            throw new ModuleErrorException(
                $this->translator->trans(
                    'This file does not seem to be a valid module zip',
                    [],
                    'Admin.Modules.Notification'
                )
            );
        }

        $zip = new ZipArchive();
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

        throw new ModuleErrorException(
            $this->translator->trans(
                'Downloaded zip file does not contain the expected module',
                [],
                'Admin.Modules.Notification'
            )
        );
    }

    public static function assertIsAddonsUrl($source): bool
    {
        return is_string($source) && 1 === preg_match(self::URL_VALIDATION_REGEX, $source);
    }

    private function assertSourceHasBeenChecked($source): void
    {
        $authenticatedQueryParameters = $this->computeAuthentication($source);
        if ($authenticatedQueryParameters['source'] !== $this->handledSource) {
            throw new SourceNotCheckedException('Method assertCanBeDownloaded() should be called first');
        }
    }

    private function computeAuthentication(string $source): array
    {
        $url_parts = parse_url($source);
        if (is_array($url_parts) && isset($url_parts['query']) && is_string($url_parts['query'])) {
            parse_str($url_parts['query'], $params);
        } else {
            $params = [];
        }

        $requestOptions = [];
        $authParams = $this->addonsDataProvider->getAuthenticationParams();
        if (is_string($authParams['bearer'])) {
            $requestOptions['headers'] = [
                'Authorization' => 'Bearer ' . $authParams['bearer'],
            ];

            $accountsShopUuid = $this->addonsDataProvider->getAccountsShopUuid();
            if (!empty($accountsShopUuid)) {
                $requestOptions['accounts_shop_uuid'] = $accountsShopUuid;
            }
        }
        if (is_array($authParams['credentials'])) {
            $params = array_merge($authParams['credentials'], $params);
        }

        $url_parts['query'] = urldecode(http_build_query($params));
        $source = http_build_url($url_parts);

        return [
            'source' => $source,
            'options' => $requestOptions,
        ];
    }

    private function isZipFile(string $file): bool
    {
        return is_file($file) && in_array(mime_content_type($file), self::AUTHORIZED_MIME);
    }
}
