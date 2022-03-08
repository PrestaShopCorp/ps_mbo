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

use Access;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Adapter\Validate;
use PrestaShopBundle\Security\Voter\PageVoter;

/**
 * Checks user access levels and permissions
 */
class PermissionChecker implements PermissionCheckerInterface
{
    /**
     * @var LegacyContext
     */
    private $context;

    public function __construct(LegacyContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthorizationLevel(string $controller): int
    {
        if ($this->isGranted(PageVoter::DELETE, $controller)) {
            return PageVoter::LEVEL_DELETE;
        }

        if ($this->isGranted(PageVoter::CREATE, $controller)) {
            return PageVoter::LEVEL_CREATE;
        }

        if ($this->isGranted(PageVoter::UPDATE, $controller)) {
            return PageVoter::LEVEL_UPDATE;
        }

        if ($this->isGranted(PageVoter::READ, $controller)) {
            return PageVoter::LEVEL_READ;
        }

        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function isGranted($attributes, string $controllerName): bool
    {
        if (!$this->context->getContext()->employee->id_profile) {
            throw new \LogicException('Cannot retrieve connected user from the context given');
        }

        // First we validate the given attributes
        $allowedPermissions = [
            PageVoter::CREATE,
            PageVoter::UPDATE,
            PageVoter::DELETE,
            PageVoter::READ,
        ];

        if (is_array($attributes)) {
            foreach ($attributes as $attribute) {
                if (!is_string($attribute)) {
                    throw new \LogicException('The permission format given is not allowed. We accept string or array');
                }
                if (!in_array(strtolower($attribute), $allowedPermissions)) {
                    throw new \LogicException(sprintf('Permission [%s] given is not known', $attribute));
                }
            }
        } elseif (is_string($attributes)) {
            if (!in_array(strtolower($attributes), $allowedPermissions)) {
                throw new \LogicException(sprintf('Permission [%s] given is not known', $attributes));
            }

            $attributes = [$attributes];
        } else {
            throw new \LogicException('The permission format given is not allowed. We accept string or array');
        }

        foreach ($attributes as $attribute) {
            if (
                Access::isGranted(
                    [
                        sprintf('ROLE_MOD_TAB_%s_%s', strtoupper($controllerName), strtoupper($attribute)),
                    ],
                    (int) $this->context->getContext()->employee->id_profile
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
