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

namespace PrestaShop\Module\Mbo\Api\Security;

use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Cloud\Kms\V1\KeyManagementServiceClient;

class KMSClient implements KMSClientInterface
{
    /**
     * @var KeyManagementServiceClient
     */
    private $client;

    /**
     * @var string
     */
    private $projectId;

    /**
     * @var string
     */
    private $locationId;

    /**
     * @var string
     */
    private $keyRingId;

    public function __construct(string $projectId, string $locationId, string $keyRingId)
    {
        // Create the Cloud KMS client.
        $this->client = new KeyManagementServiceClient();
        $this->projectId = $projectId;
        $this->locationId = $locationId;
        $this->keyRingId = $keyRingId;
    }

    /**
     * @throws ApiException
     */
    public function generateKey(
        string $cryptoKey,
        string $cryptoKeyVersion,
        array $data
    ): array {
        try {
            $formattedName = $this->client->cryptoKeyVersionName(
                $this->projectId,
                $this->locationId,
                $this->keyRingId,
                $cryptoKey,
                $cryptoKeyVersion
            );
            $data = implode('#', $data);
            $response = $this->client->macSign($formattedName, $data);
        } finally {
            $this->client->close();
        }

        return json_decode($response->serializeToJsonString(), true);
    }

    public function validateKey(
        string $cryptoKey,
        string $cryptoKeyVersion,
        string $mac,
        array $data
    ): array {
        try {
            $formattedName = $this->client->cryptoKeyVersionName(
                $this->projectId,
                $this->locationId,
                $this->keyRingId,
                $cryptoKey,
                $cryptoKeyVersion
            );
            $data = implode('#', $data);
            $response = $this->client->macVerify($formattedName, $data, $mac);
        } finally {
            $this->client->close();
        }
        var_dump($response->getSuccess());

        return json_decode($response->serializeToJsonString(), true);
    }

    /**
     * @throws ValidationException
     */
    public function listKeys(
        string $keyRingId
    ): \Iterator {
        try {
            $cryptoKeys = $this->client->listCryptoKeys($keyRingId);
        } catch (ApiException $e) {
            var_dump($e->getMessage());

            return null;
        }

        return $cryptoKeys->getIterator();
    }

    /**
     * @throws ApiException|ValidationException
     */
    public function listKeyRings(): \Iterator
    {
        // Call the API.
        $keyRings = $this->client->listKeyRings($this->getLocationName());

        return $keyRings->getIterator();
    }

    /**
     * Build the parent location name.
     */
    private function getLocationName(): string
    {
        return $this->client->locationName($this->projectId, $this->locationId);
    }
}
