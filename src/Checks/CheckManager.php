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

namespace PrestaShop\Module\Mbo\Checks;

use Exception;
use PrestaShop\Module\Mbo\Checks\Exception\CheckFailedException;

class CheckManager
{
    /**
     * @var CheckInterface[]
     */
    private $checks;

    public function __construct(array $checks)
    {
        $this->checks = $checks;
    }

    public function process(): array
    {
        $results = [];

        foreach ($this->checks as $check) {
            try {
                $checkSucceeded = $check->check();
            } catch (CheckFailedException | Exception $e) {
                $checkSucceeded = false;
                $failureReason = $e->getMessage();
            }

            $results[] = [
                'name' => $check->getName(),
                'description' => $check->getDescription(),
                'result' => $checkSucceeded,
                'message' => $failureReason ?? '',
            ];
        }

        return $results;
    }

}
