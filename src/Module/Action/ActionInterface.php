<?php

namespace PrestaShop\Module\Mbo\Module\Action;

interface ActionInterface
{
    const PENDING = 'PENDING';
    const PROCESSING = 'PROCESSING';
    const PROCESSED = 'PROCESSED';

    public function execute(): bool;

    public function getActionName(): string;

    public function getModuleName(): string;

    public function getStatus(): string;

    public function setStatus(string $status): ActionInterface;

    public function getParameters(): ?array;

    public function isInProgress(): bool;

    public function isPending(): bool;

    public function isProcessed(): bool;
}
