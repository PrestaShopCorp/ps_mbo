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

use PrestaShop\Module\Mbo\Distribution\Client;
use PrestaShop\PrestaShop\Core\Module\ModuleManager;

class InstallAction extends AbstractAction
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

    /**
     * @var string|null
     */
    private $source;
    /**
     * @var Client
     */
    private $distributionApi;

    public function __construct(
        ModuleManager $moduleManager,
        Client        $distributionApi,
        string        $actionUuid,
        string        $moduleName,
        ?string       $source = null,
        ?string       $status = ActionInterface::PENDING
    ) {
        $this->moduleManager = $moduleManager;
        $this->distributionApi = $distributionApi;
        $this->actionUuid = $actionUuid;
        $this->moduleName = $moduleName;
        $this->source = $source;

        parent::__construct($status);
    }

    public function execute(): bool
    {
        // Notify Distribution API that install action is being process
        $this->distributionApi->notifyStartInstall($this);

        if ($this->moduleManager->install($this->moduleName, $this->source)) {
            // Notify Distribution API that install action have been processed
            $this->distributionApi->notifyEndInstall($this);

            return true;
        }

        return false;
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

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getActionName(): string
    {
        return ActionBuilder::ACTION_NAME_INSTALL;
    }

    public function getParameters(): ?array
    {
        return (null !== $this->getSource()) ? [
            'source' => $this->getSource(),
        ] : null;
    }
}
