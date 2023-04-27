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

use PrestaShop\PrestaShop\Core\Module\ModuleManager;

class UninstallAction extends AbstractAction
{
    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var string
     */
    private $actionUuid;

    /**
     * @var string
     */
    private $moduleName;

    public function __construct(
        ModuleManager $moduleManager,
        string        $actionUuid,
        string        $moduleName,
        ?string       $status = ActionInterface::PENDING
    ) {
        $this->moduleManager = $moduleManager;
        $this->actionUuid = $actionUuid;
        $this->moduleName = $moduleName;

        parent::__construct($status);
    }

    public function execute(): bool
    {
        return $this->moduleManager->uninstall($this->moduleName);
    }

    public function getModuleManager(): ModuleManager
    {
        return $this->moduleManager;
    }

    public function getActionUuid(): string
    {
        return $this->actionUuid;
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getActionName(): string
    {
        return ActionBuilder::ACTION_NAME_UNINSTALL;
    }

    public function getParameters(): ?array
    {
        return null;
    }
}
