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
