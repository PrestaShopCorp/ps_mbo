<?php

namespace PrestaShop\Module\Mbo\Module\Action;

use PrestaShop\PrestaShop\Core\Module\ModuleManager;

class UpgradeAction extends AbstractAction
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

    public function __construct(
        ModuleManager $moduleManager,
        string        $actionUuid,
        string        $moduleName,
        ?string       $source = null,
        ?string       $status = ActionInterface::PENDING
    ) {
        $this->moduleManager = $moduleManager;
        $this->actionUuid = $actionUuid;
        $this->moduleName = $moduleName;
        $this->source = $source;

        parent::__construct($status);
    }

    public function execute(): bool
    {
        return $this->moduleManager->upgrade($this->moduleName, $this->source);
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
        return ActionBuilder::ACTION_NAME_UPGRADE;
    }

    public function getParameters(): ?array
    {
        return (null !== $this->getSource()) ? [
            'source' => $this->getSource(),
        ] : null;
    }
}
