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

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Utils;
use PrestaShop\Module\Mbo\Addons\Provider\AddonsDataProvider;
use PrestaShop\Module\Mbo\Module\Exception\ModuleUpgradeFailedException;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\Exception\SourceNotHandledException;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\SourceHandlerInterface;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\ZipSourceHandler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddonsUrlSourceHandler implements SourceHandlerInterface
{
    private const URL_VALIDATION_REGEX = "/^https?:\\/\\/(?:www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{0,256}api-addons\\.prestashop\\.com(?:[-a-zA-Z0-9()@:%_\\+.~#?&\\/=]*)$/";

    private const ZIP_FILENAME_PATTERN = '/(\w+)\.zip\b/';


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
     * @var ZipSourceHandler
     */
    private $zipSourceHandler;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        AddonsDataProvider $addonsDataProvider,
        ZipSourceHandler $zipSourceHandler,
        TranslatorInterface $translator,
        string $modulePath
    ) {
        $this->addonsDataProvider = $addonsDataProvider;
        $this->zipSourceHandler = $zipSourceHandler;
        $this->translator = $translator;
        $this->modulePath = rtrim($modulePath, '/') . '/';

        $this->httpClient = $client = new Client([
            'timeout' => '7200',
            'CURLOPT_FORBID_REUSE' => true,
            'CURLOPT_FRESH_CONNECT' => true,
        ]);
    }

    public function canHandle($source): bool
    {
        if (!is_string($source) || 1 !== preg_match(self::URL_VALIDATION_REGEX, $source)) {
            return false;
        }

        try {
            $authenticatedQueryParameters = $this->computeAuthentication($source);
            $source = $authenticatedQueryParameters['source'];

            $response = $this->httpClient->request('HEAD', $source, $authenticatedQueryParameters['options']);
        } catch (TransportExceptionInterface $e) {
            return false;
        } catch (\Exception $e) {
            throw new ModuleUpgradeFailedException(
                $this->translator->trans(
                    'Cannot download module upgrade file. Please check that you\'re allowed and if applicable, that your Business Care subscription is active',
                    [],
                    'Modules.Mbo.Errors'
                )
            );
        }

        $this->moduleName = null;

        if (preg_match(self::ZIP_FILENAME_PATTERN, $source, $moduleName) === 1) {
            $this->moduleName = $moduleName[1];
        }

        $headers = $response->getHeaders(false);

        $b = reset($headers['Content-Disposition']);
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
            $this->handledSourceCredentials = $authenticatedQueryParameters['options'];

            return true;
        }

        return false;
    }

    public function getModuleName($source): ?string
    {
        $this->assertSourceHasBeenChecked($source);

        return $this->moduleName;
    }

    public function handle(string $source): void
    {
        $this->assertSourceHasBeenChecked($source);

        $filesystem = new Filesystem();
        $path = $this->getDownloadDir($this->getModuleName($this->handledSource));
        $stream = Utils::streamFor($path);
        $filesystem->dumpFile(
            $path,
            $this->httpClient->request(
                'GET',
                $this->handledSource,
                array_merge(['sink' => $stream], $this->handledSourceCredentials)
            )->getBody()
        );
        $this->zipSourceHandler->handle($path);
        @unlink($path);
    }

    private function getDownloadDir(string $moduleName): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->modulePath, $moduleName . '.zip']);
    }

    private function assertSourceHasBeenChecked($source): void
    {
        $authenticatedQueryParameters = $this->computeAuthentication($source);
        if ($authenticatedQueryParameters['source'] !== $this->handledSource) {
            throw new SourceNotHandledException('Method canHandle() should be called first');
        }
    }

    private function computeAuthentication(string $source)
    {
        $source = str_replace('https://testmbo50.demo-hawks.prestashop.net', 'https://fionaversion8test.demo-cratik.prestashop.net', $source);
        $url_parts = parse_url($source);
        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $params);
        } else {
            $params = [];
        }

        $requestOptions = [];
        $authParams = $this->addonsDataProvider->getAuthenticationParams();
        if (null !== $authParams['bearer'] && is_string($authParams['bearer'])) {
            $requestOptions['headers'] = [
                'Authorization' => 'Bearer ' . $authParams['bearer'],
            ];
        }
        if (null !== $authParams['credentials'] && is_array($authParams['credentials'])) {
            $params = array_merge($authParams['credentials'], $params);
        }

        $url_parts['query'] = urldecode(http_build_query($params));
        $source = http_build_url($url_parts);

        return [
            'source' => $source,
            'options' => $requestOptions,
        ];
    }
}
