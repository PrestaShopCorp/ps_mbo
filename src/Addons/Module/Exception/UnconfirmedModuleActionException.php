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

namespace PrestaShop\Module\Mbo\Addons\Module\Exception;

use PrestaShop\Module\Mbo\Addons\Module;
use PrestaShop\PrestaShop\Core\Exception\CoreException;

/**
 * This class is used for the module page, which allows to ask for a confirmation from the employee.
 */
class UnconfirmedModuleActionException extends CoreException
{
    /**
     * Concerned module by the exception.
     *
     * @var Module
     */
    protected $module;

    /**
     * Action requested by the employee.
     *
     * @var string
     */
    protected $action;

    /**
     * Subject to send in order to confirm.
     *
     * @var string
     */
    protected $subject;

    /**
     * Module getter.
     *
     * @return Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Action getter (install, uninstall, reset ...).
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Subject getter (PrestaTrust...).
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Module setter.
     *
     * @param Module $module
     *
     * @return $this
     */
    public function setModule(Module $module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Action setter.
     *
     * @param string $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Subject setter.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }
}
