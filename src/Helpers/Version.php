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
 * Api send us version formatted like 8000000
 * Where we have 3 digits per points => 8000000 => 8.000.000 => 8.0.0
 *
 * This helper is here to facilitate conversion & comparison
 */
class Version
{
    /**
     * @param int $apiFormattedVersion
     *
     * @return string
     */
    public static function convertFromApi(int $apiFormattedVersion): string
    {
        // Reverse the string, split every 3 char, and reverse the array
        $arrayVersion = array_reverse(array_map('strrev', str_split(strrev((string) $apiFormattedVersion), 3)));

        // Apply an intval (to have 8.10.1 instead of 08.010.001) and implode to send back the version in string
        return implode('.', array_map('intval', $arrayVersion));
    }

    /**
     * @param string $phpFormattedVersion
     *
     * @return int
     */
    public static function convertToApi(string $phpFormattedVersion): int
    {
        // Explode the version
        $arrayVersion = explode('.', $phpFormattedVersion);

        foreach ($arrayVersion as $k => $versionNumber) {
            $arrayVersion[$k] = str_pad($versionNumber, 3, '0', STR_PAD_LEFT);
        }

        return intval(implode($arrayVersion));
    }
}
