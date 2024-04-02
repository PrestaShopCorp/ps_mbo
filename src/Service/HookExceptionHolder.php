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

namespace PrestaShop\Module\Mbo\Service;

use Exception;

/**
 * Because the Core's HookManager makes the exceptions from hooks silent,
 * this service will hold exceptions from hooks in case we want to reuse/log/throw them
 */
class HookExceptionHolder
{
    private $listenedHooks = [];

    public function reset(): void
    {
        $this->listenedHooks = [];
    }

    public function listenFor(string $hookName): void
    {
        $this->listenedHooks[$hookName] = [
            'exception' => null,
        ];
    }

    public function holdException(string $hookName, Exception $exception): void
    {
        if (!array_key_exists($hookName, $this->listenedHooks)) {
            // Hook exceptions not listened. Throw Exception ? log ?
            return;
        }

        $this->listenedHooks[$hookName]['exception'] = $exception;
    }

    public function getLastException(string $hookName): ?Exception
    {
        if (!array_key_exists($hookName, $this->listenedHooks)) {
            // Hook exceptions not listened. Throw Exception ? log ?
            return null;
        }

        return $this->listenedHooks[$hookName]['exception'];
    }
}
