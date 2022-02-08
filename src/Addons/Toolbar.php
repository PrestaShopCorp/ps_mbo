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

namespace PrestaShop\Module\Mbo\Addons;

use PrestaShop\Module\Mbo\Controller\Admin\ModuleCatalogController;
use PrestaShop\Module\Mbo\Security\PermissionCheckerInterface;
use PrestaShopBundle\Security\Voter\PageVoter;
use Symfony\Component\Translation\TranslatorInterface;

class Toolbar
{
    /**
     * @var PermissionCheckerInterface
     */
    private $permissionChecker;

    /**
     * @var DataProvider
     */
    private $addonsDataProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        PermissionCheckerInterface $permissionChecker,
        DataProvider $addonsDataProvider,
        TranslatorInterface $translator
    ) {
        $this->permissionChecker = $permissionChecker;
        $this->addonsDataProvider = $addonsDataProvider;
        $this->translator = $translator;
    }

    /**
     * Common method for all module related controller for getting the header buttons.
     *
     * @return array
     */
    public function getToolbarButtons(): array
    {
        if (!in_array(
            $this->permissionChecker->getAuthorizationLevel(ModuleCatalogController::CONTROLLER_NAME),
            [
                PageVoter::LEVEL_READ,
                PageVoter::LEVEL_UPDATE,
            ]
        )) {
            return array_merge(
                $this->getAddModuleToolbar(),
                $this->getConnectionToolbar()
            );
        }

        return [];
    }

    /**
     * Get the Add Module button definition for the toolbar
     *
     * @return array[]
     */
    public function getAddModuleToolbar(): array
    {
        return [
            'add_module' => [
                'href' => '#',
                'desc' => $this->translator->trans('Upload a module', [], 'Admin.Modules.Feature', $this->translator->getLocale()),
                'icon' => 'cloud_upload',
                'help' => $this->translator->trans('Upload a module', [], 'Admin.Modules.Feature', $this->translator->getLocale()),
            ],
        ];
    }

    /**
     * Returns button definition for Addons login/logout, depending on the user authentication status.
     *
     * @return array
     */
    public function getConnectionToolbar(): array
    {
        if ($this->addonsDataProvider->isAddonsAuthenticated()) {
            $toolbarButtons['addons_logout'] = [
                'href' => '#',
                'desc' => $this->addonsDataProvider->getAddonsEmail(),
                'icon' => 'exit_to_app',
                'help' => $this->translator->trans('Synchronized with Addons marketplace!', [], 'Admin.Modules.Notification', $this->translator->getLocale()),
            ];
        } else {
            $toolbarButtons['addons_connect'] = [
                'href' => '#',
                'desc' => $this->translator->trans('Connect to Addons marketplace', [], 'Feature', $this->translator->getLocale()),
                'icon' => 'vpn_key',
                'help' => $this->translator->trans('Connect to Addons marketplace', [], 'Feature', $this->translator->getLocale()),
            ];
        }

        return $toolbarButtons;
    }
}
