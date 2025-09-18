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

namespace PrestaShop\Module\Mbo\Module\Workflow;

use PrestaShop\Module\Mbo\Helpers\ModuleErrorHelper;
use PrestaShop\Module\Mbo\Module\Exception\TransitionFailedException;
use PrestaShop\Module\Mbo\Module\TransitionModule;
use PrestaShop\Module\Mbo\Module\Workflow\Exception\UnknownTransitionException;
use Symfony\Component\String\UnicodeString;

if (!defined('_PS_VERSION_')) {
    exit;
}

class TransitionApplier
{
    /**
     * @var TransitionsManager
     */
    private $transitionsManager;

    public function __construct(
        TransitionsManager $transitionsManager,
    ) {
        $this->transitionsManager = $transitionsManager;
    }

    /**
     * @throws \Exception
     */
    public function apply(TransitionModule $module, string $transitionName, array $context = [])
    {
        $method = (new UnicodeString($transitionName))->camel()->toString();
        $executionContext = [
            'transition' => $transitionName,
            'moduleName' => $module->getName(),
            'moduleVersion' => $module->getVersion(),
            'context' => $context,
        ];

        try {
            if (!method_exists($this->transitionsManager, $method)) {
                throw new UnknownTransitionException($transitionName, $executionContext);
            }

            if (!$this->transitionsManager->{$method}($module, $context)) {
                throw new TransitionFailedException($transitionName, $executionContext);
            }
        } catch (\Exception $e) {
            throw ModuleErrorHelper::reportAndConvertError($e, $executionContext);
        }
    }
}
