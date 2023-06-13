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

use PrestaShop\Module\Mbo\Module\Module;

interface ActionInterface
{
    const PENDING = 'PENDING';
    const PROCESSING = 'PROCESSING';
    const PROCESSED = 'PROCESSED';
    const ERROR = 'ERROR';

    public function execute(): bool;

    public function getActionUuid(): string;

    public function getActionName(): string;

    public function getModuleName(): string;

    public function getStatus(): string;

    public function setStatus(string $status): ActionInterface;

    public function getParameters(): ?array;

    public function isInProgress(): bool;

    public function isPending(): bool;

    public function isProcessed(): bool;

    public function getModule(): Module;

    public function refreshModule(): void;
}
