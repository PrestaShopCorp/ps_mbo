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

namespace PrestaShop\Module\Mbo\Helpers;

/**
 * The purpose of this class is to provide a way to make asynchronous HTTP requests.
 * GuzzleHttp\Client::requestAsync() is not used because it does not allow "Fire and Forget" requests.
 */
class AsyncClient
{
    public const METHOD_POST = 'POST';

    public const METHOD_GET = 'GET';

    /**
     * Process an async request using the socket connection
     *
     * @param string $url
     * @param array $params
     * @param array $customHeaders
     * @param string $method
     *
     * @return bool
     */
    public static function request(string $url, array $params = [], array $customHeaders = [], string $method = self::METHOD_POST): bool
    {
        $endpointParts = parse_url($url);
        $endpointParts['path'] = $endpointParts['path'] ?? '/';
        $endpointParts['port'] = $endpointParts['port'] ?? ($endpointParts['scheme'] === 'https' ? 443 : 80);
        $socket = self::openSocket($endpointParts['host'], $endpointParts['port']);

        if (!$socket) {
            return false;
        }

        if ($method === self::METHOD_GET) {
            return self::get($endpointParts, $socket, $customHeaders);
        }

        return self::post($endpointParts, $socket, $params, $customHeaders);
    }

    private static function get(array $endpointParts, $socket, array $customHeaders = []): bool
    {
        if (!empty($endpointParts['query'])) {
            $endpointParts['path'] .= '?' . $endpointParts['query'];
        }
        $request = "GET {$endpointParts['path']} HTTP/1.1\r\n";
        $request .= "Host: {$endpointParts['host']}\r\n";
        foreach ($customHeaders as $header) {
            $request .= "{$header}\r\n";
        }
        $request .= "Content-Type: application/json\r\n\r\n";
        $request .= "Connection:Close\r\n\r\n";

        fwrite($socket, $request);
        fclose($socket);

        return true;
    }

    private static function post(array $endpointParts, $socket, array $postData = [], array $customHeaders = []): bool
    {
        $encodedPostData = http_build_query($postData, '', '&');
        $contentLength = strlen($encodedPostData);

        $request = "POST {$endpointParts['path']} HTTP/1.1\r\n";
        $request .= "Accept: application/json\r\n";
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Host: {$endpointParts['host']}\r\n";
        foreach ($customHeaders as $header) {
            $request .= "{$header}\r\n";
        }
        $request .= "Content-Length: {$contentLength}\r\n\r\n";
        $request .= $encodedPostData;

        fwrite($socket, $request);
        fclose($socket);

        return true;
    }

    private static function openSocket(string $host, int $port)
    {
        try {
            return fsockopen($host, $port, $errno, $errstr, 0.1);
        } catch (\Exception $e) {
            return false;
        }
    }
}
