<?php

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
