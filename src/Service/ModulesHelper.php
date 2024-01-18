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

namespace PrestaShop\Module\Mbo\Service;

use PrestaShop\Module\Mbo\Module\Repository;

class ModulesHelper
{
    /**
     * @var Repository
     */
    private $moduleRepository;

    public function __construct(Repository $repository)
    {
        $this->moduleRepository = $repository;
    }

    public function findForUpdates(string $moduleName): array
    {
        $currentVersion = $availableVersion = null;
        $upgradeAvailable = false;

        $db = \Db::getInstance();
        $request = 'SELECT `version` FROM `' . _DB_PREFIX_ . "module` WHERE name='" . $moduleName . "'";

        /** @var string|false $moduleCurrentVersion */
        $moduleCurrentVersion = $db->getValue($request);

        if ($moduleCurrentVersion) {
            $currentVersion = $moduleCurrentVersion;
        }

        // We need to clear cache to get fresh data from addons
        $this->moduleRepository->clearCache();

        $module = $this->moduleRepository->getApiModule($moduleName);

        if (null !== $module) {
            $availableVersion = (string)$module->version_available;

            // If the current installed version is greater or equal than the one returned by Addons, an upgrade is available
            if (
                null !== $availableVersion
                && null !== $currentVersion
                && version_compare($availableVersion, $currentVersion, 'gt')
            ) {
                $upgradeAvailable = true;
            }
        }

        return [
            'current_version' => $currentVersion,
            'available_version' => $availableVersion,
            'upgrade_available' => $upgradeAvailable,
        ];
    }
}
