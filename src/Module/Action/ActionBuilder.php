<?php

namespace PrestaShop\Module\Mbo\Module\Action;

use PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider;
use PrestaShop\Module\Mbo\Distribution\Client;
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

    public function __construct(
        ModuleManager $moduleManager,
        Client $distributionApi,
        AdminAuthenticationProvider $adminAuthenticationProvider
    )
    {
        $this->moduleManager = $moduleManager;
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
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_UPGRADE:
                UpgradeAction::validateActionData($actionData);

                return new UpgradeAction(
                    $this->moduleManager,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['source'] ?? null,
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_ENABLE:
                EnableAction::validateActionData($actionData);

                return new EnableAction(
                    $this->moduleManager,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_DISABLE:
                DisableAction::validateActionData($actionData);

                return new DisableAction(
                    $this->moduleManager,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_ENABLE_MOBILE:
                EnableMobileAction::validateActionData($actionData);

                return new EnableMobileAction(
                    $this->moduleManager,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_DISABLE_MOBILE:
                DisableMobileAction::validateActionData($actionData);

                return new DisableMobileAction(
                    $this->moduleManager,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            case self::ACTION_NAME_RESET:
                ResetAction::validateActionData($actionData);

                return new ResetAction(
                    $this->moduleManager,
                    $actionData['action_uuid'] ?? Uuid::uuid4()->toString(),
                    $actionData['module_name'],
                    $actionData['status'] ?? ActionInterface::PENDING
                );
            default:
                throw new \InvalidArgumentException('Unrecognized module action name');
        }
    }

}
