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
use PrestaShop\Module\Mbo\Module\Module;
use PrestaShop\Module\Mbo\Module\Repository;
use PrestaShop\PrestaShop\Core\Module\ModuleManager;

abstract class AbstractAction implements ActionInterface
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var string
     */
    protected $actionUuid;

    /**
     * @var string
     */
    protected $moduleName;
    /**
     * @var Client
     */
    protected $distributionApi;
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var Module
     */
    protected $module;

    public function __construct(
        ModuleManager $moduleManager,
        Repository    $repository,
        Client        $distributionApi,
        string        $actionUuid,
        string        $moduleName,
        string        $status = ActionInterface::PENDING
    ) {
        $this->moduleManager = $moduleManager;
        $this->distributionApi = $distributionApi;
        $this->actionUuid = $actionUuid;
        $this->moduleName = $moduleName;
        $this->status = $status;
        $this->repository = $repository;
    }

    public function execute(): bool
    {
        throw new \Exception('Method execute must be implemented. You\'re using the abstract action.');
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

    public function getParameters(): ?array
    {
        return null;
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

    public function refreshModule(): void
    {
        $this->repository->clearCache();
        $this->module = $this->repository->getModule($this->moduleName);
    }

    /**
     * @return Module
     */
    public function getModule(): Module
    {
        if (null === $this->module) {
            $this->refreshModule();
        }

        return $this->module;
    }

    public static function validateActionData(array $actionData)
    {
        if (empty($actionData['module_name']) || !is_string($actionData['module_name'])) {
            throw new \InvalidArgumentException('Action definition requirements are not met : module_name cannot be empty');
        }
    }
}
