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

namespace PrestaShop\Module\Mbo\Api\Service;

use Google\ApiCore\ValidationException;
use Google\Cloud\Kms\V1\CryptoKey;
use Google\Cloud\Kms\V1\KeyRing;
use PrestaShop\Module\Mbo\Api\Security\KMSClientInterface;

class ApiAuthorizationService
{
    /**
     * @var KMSClientInterface
     */
    private $KMSClient;

    public function __construct(
        KMSClientInterface $KMSClient
    ) {
        $this->KMSClient = $KMSClient;
    }

    /**
     * Authorizes if the call to endpoint is legit and creates sync state if needed
     *
     * @throws ValidationException
     */
    public function authorizeCall(): bool
    {
        if (!$this->validateKey()) {
            return false;
        }

        return true;
    }

    /**
     * @throws ValidationException
     */
    private function validateKey(): bool
    {
        try {
            $keyRingsList = $this->KMSClient->listKeyRings();
        } catch (\Exception $e) {
            return false;
        }

        $keys = [];

        /** @var KeyRing $keyRing */
        foreach ($keyRingsList as $keyRing) {
            $keyRingKeys = $this->KMSClient->listKeys($keyRing->getName());

            /** @var CryptoKey $cryptoKey */
            foreach ($keyRingKeys as $cryptoKey) {
                $keys[] = $cryptoKey->getName();
            }
        }

        return !empty($keys);
    }
}
