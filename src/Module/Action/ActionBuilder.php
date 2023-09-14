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

namespace PrestaShop\Module\Mbo\Module\Action;

use PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider;
use PrestaShop\Module\Mbo\Distribution\Client;
use PrestaShop\Module\Mbo\Module\Repository;
use PrestaShop\PrestaShop\Core\Module\ModuleManager;
use Ramsey\Uuid\Uuid;

class ActionBuilder
{
    const ACTION_NAME_INSTALL = 'install';
    const ACTION_NAME_UNINSTALL = 'uninstall';
    const ACTION_NAME_UPGRADE = 'upgrade';
    const ACTION_NAME_ENABLE = 'enable';
    const ACTION_NAME_DISABLE = 'disable';
    const ACTION_NAME_ENABLE_MOBILE = 'enable_mobile';
    const ACTION_NAME_DISABLE_MOBILE = 'disable_mobile';
    const ACTION_NAME_RESET = 'reset';
    /**
     * @var ModuleManager
     */
    private $moduleManager;
    /**
     * @var Client
     */
    private $distributionApi;
    /**
     * @var AdminAuthenticationProvider
     */
    private $adminAuthenticationProvider;
    /**
     * @var Repository
     */
    private $repository;

    public function __construct(
        ModuleManager $moduleManager,
        Repository $repository,
        Client $distributionApi,
        AdminAuthenticationProvider $adminAuthenticationProvider
    )
    {
        $this->moduleManager = $moduleManager;
        $this->repository = $repository;
        $this->distributionApi = $distributionApi;
        $this->distributionApi->setBearer($adminAuthenticationProvider->getMboJWT());
    }

    public function build(array $actionData): ActionInterface
    {
        // First validate than the expected parameters are given
        if (empty($actionData['action'])) {
            throw new \InvalidArgumentException('Action definition requirements are not met : action name parameter cannot be empty');
        }

        switch ($actionData['action']) {
            case self::ACTION_NAME_INSTALL:
                InstallAction::validateActionData($actionData);

                return new InstallAction(
                    $this->moduleManager,
                    $this->repository,
                    $this->distributionApi,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['source'] ?? null,
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_UNINSTALL:
                UninstallAction::validateActionData($actionData);

                return new UninstallAction(
                    $this->moduleManager,
                    $this->repository,
                    $this->distributionApi,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_UPGRADE:
                UpgradeAction::validateActionData($actionData);

                return new UpgradeAction(
                    $this->moduleManager,
                    $this->repository,
                    $this->distributionApi,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['source'] ?? null,
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_ENABLE:
                EnableAction::validateActionData($actionData);

                return new EnableAction(
                    $this->moduleManager,
                    $this->repository,
                    $this->distributionApi,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_DISABLE:
                DisableAction::validateActionData($actionData);

                return new DisableAction(
                    $this->moduleManager,
                    $this->repository,
                    $this->distributionApi,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_ENABLE_MOBILE:
                EnableMobileAction::validateActionData($actionData);

                return new EnableMobileAction(
                    $this->moduleManager,
                    $this->repository,
                    $this->distributionApi,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_DISABLE_MOBILE:
                DisableMobileAction::validateActionData($actionData);

                return new DisableMobileAction(
                    $this->moduleManager,
                    $this->repository,
                    $this->distributionApi,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_RESET:
                ResetAction::validateActionData($actionData);

                return new ResetAction(
                    $this->moduleManager,
                    $this->repository,
                    $this->distributionApi,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            default:
                throw new \InvalidArgumentException('Unrecognized module action name');
        }
    }

}
