<?php

namespace PrestaShop\Module\Mbo\Module\Action;

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
    private $moduleName;

    /**
     * @var string|null
     */
    private $source;

    public function __construct(
        ModuleManager $moduleManager,
        string        $moduleName,
        ?string       $source = null,
        ?string       $status = ActionInterface::PENDING
    ) {
        $this->moduleManager = $moduleManager;
        $this->moduleName = $moduleName;
        $this->source = $source;

        parent::__construct($status);
    }

    public function execute(): bool
    {
        return $this->moduleManager->install($this->moduleName, $this->source);
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

    /**
     * @return string|null
     */
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
