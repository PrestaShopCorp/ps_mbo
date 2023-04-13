<?php

namespace PrestaShop\Module\Mbo\Module\Action;

abstract class AbstractAction implements ActionInterface
{
    protected $status;

    public function __construct(string $status)
    {
        $this->status = $status;
    }

    public function execute(): bool
    {
        throw new \Exception('Method execute must be implented. You\'re using the abstract action.');
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): ActionInterface
    {
        $this->status = $status;

        return $this;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::PROCESSING;
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::PROCESSED;
    }

    public static function validateActionData(array $actionData)
    {
        if (empty($actionData['module_name']) || !is_string($actionData['module_name'])) {
            throw new \InvalidArgumentException('Action definition requirements are not met : module_name cannot be empty');
        }
    }
}
