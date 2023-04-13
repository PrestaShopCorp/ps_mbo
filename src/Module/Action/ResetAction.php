<?php

namespace PrestaShop\Module\Mbo\Module\Action;

use PrestaShop\PrestaShop\Core\Module\ModuleManager;

class ResetAction extends AbstractAction
{
    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var string
     */
    private $moduleName;

    public function __construct(
        ModuleManager $moduleManager,
        string        $moduleName,
        ?string       $status = ActionInterface::PENDING
    ) {
        $this->moduleManager = $moduleManager;
        $this->moduleName = $moduleName;

        parent::__construct($status);
    }

    public function execute(): bool
    {
        return $this->moduleManager->reset($this->moduleName);
    }

    /**
     * @return ModuleManager
     */
    public function getModuleManager(): ModuleManager
    {
        return $this->moduleManager;
    }

    /**
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getActionName(): string
    {
        return ActionBuilder::ACTION_NAME_RESET;
    }

    public function getParameters(): ?array
    {
        return null;
    }
}
