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

namespace PrestaShop\Module\Mbo\Api\Client;

use GuzzleHttp\Message\ResponseInterface;

class ResponseApiHandler
{
    /**
     * Format api response.
     *
     * @param ResponseInterface $response
     *
     * @return array
     */
    public function handleResponse(ResponseInterface $response): array
    {
        $responseContents = json_decode($response->getBody()->getContents(), true);

        return [
            'status' => $this->responseIsSuccessful($responseContents, $response->getStatusCode()),
            'httpCode' => $response->getStatusCode(),
            'body' => $responseContents,
        ];
    }

    /**
     * Check if the response is successful or not (response code 200 to 299).
     *
     * @param array $responseContents
     * @param int $httpStatusCode
     *
     * @return bool
     */
    private function responseIsSuccessful(array $responseContents, int $httpStatusCode): bool
    {
        // Directly return true, no need to check the body for a 204 status code
        if (204 === $httpStatusCode) {
            return true;
        }

        return '2' === substr((string) $httpStatusCode, 0, 1) && !empty($responseContents);
    }
}
