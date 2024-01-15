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

use PrestaShop\Module\Mbo\Addons\Provider\AddonsDataProvider;
use PrestaShop\Module\Mbo\Controller\Admin\ModuleCatalogController;
use PrestaShop\Module\Mbo\Security\PermissionCheckerInterface;
use PrestaShopBundle\Security\Voter\PageVoter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This service returns descriptions for the buttons to add into the Module configure toolbar
 * (like addons connect, update module CTA, ...)
 */
class Toolbar
{
    /**
     * @var PermissionCheckerInterface
     */
    private $permissionChecker;

    /**
     * @var AddonsDataProvider
     */
    private $addonsDataProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        PermissionCheckerInterface $permissionChecker,
        AddonsDataProvider $addonsDataProvider,
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
                'desc' => $this->translator->trans(
                    'Upload a module',
                    [],
                    'Modules.Mbo.Modulescatalog',
                    $this->translator->getLocale()
                ),
                'icon' => 'cloud_upload',
                'help' => $this->translator->trans(
                    'Upload a module',
                    [],
                    'Modules.Mbo.Modulescatalog',
                    $this->translator->getLocale()
                ),
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
        $toolbarButtons = [];

        if (
            $this->addonsDataProvider->isUserAuthenticated()
            && $this->addonsDataProvider->isUserAuthenticatedOnAccounts()
        ) {
            $toolbarButtons['accounts_logout'] = $this->getAccountsStatusButton();
        }

        return $toolbarButtons;
    }

    public function getAccountsStatusButton(): ?array
    {
        if (!$this->addonsDataProvider->isUserAuthenticatedOnAccounts()) {
            return null;
        }

        return [
            'href' => '#',
            'desc' => $this->translator->trans(
                'Connected',
                [],
                'Modules.Mbo.Modulescatalog',
                $this->translator->getLocale()
            ),
            'help' => $this->translator->trans(
                'Connected as',
                [],
                'Modules.Mbo.Modulescatalog',
                $this->translator->getLocale()
            ) . ' &#013;&#010; ' . $this->addonsDataProvider->getAuthenticatedUserEmail()
        ];
    }
}
