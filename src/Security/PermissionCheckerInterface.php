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

namespace PrestaShop\Module\Mbo\Security;

interface PermissionCheckerInterface
{
    /**
     * Checks if the attributes are granted against the current authentication token and optionally supplied object.
     *
     * @see \PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController
     *
     * @param string $controller name of the controller that token is tested against
     *
     * @return int
     */
    public function getAuthorizationLevel(string $controller): int;

    /**
     * Checks if the attributes are granted against the current authentication token for a given controller.
     *
     * @see \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     * If attributes is an array of permissions, if one of the permission is granted, this method will return true.
     *
     * @param string|array $attributes Can be an array of permissions or a single one
     * @param string $controllerName The controller which permissions we check. It's the legacy name of the controller
     *
     * @return bool Whether the level of permission (attributes) is allowed for the specific controller
     */
    public function isGranted($attributes, string $controllerName): bool;
}
