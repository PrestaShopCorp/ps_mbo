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

namespace PrestaShop\Module\Mbo\Tab;

class Tab implements TabInterface
{
    /**
     * @var string class name of the tab
     */
    protected $legacyClassName;
    /**
     * @var string class name of the tab
     */
    protected $displayMode;

    /**
     * {@inheritdoc}
     */
    public function getLegacyClassName(): string
    {
        return $this->legacyClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function setLegacyClassName(string $legacyClassName): TabInterface
    {
        $this->legacyClassName = $legacyClassName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayMode(): string
    {
        return $this->displayMode;
    }

    /**
     * {@inheritdoc}
     */
    public function setDisplayMode(string $displayMode): TabInterface
    {
        $this->displayMode = $displayMode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldDisplayAfterContent(): bool
    {
        return in_array($this->legacyClassName, static::TABS_WITH_RECOMMENDED_MODULES_AFTER_CONTENT);
    }

    /**
     * {@inheritdoc}
     */
    public static function mayDisplayRecommendedModules(string $controllerName): bool
    {
        return in_array($controllerName, static::TABS_WITH_RECOMMENDED_MODULES_AFTER_CONTENT)
            || in_array($controllerName, static::TABS_WITH_RECOMMENDED_MODULES_BUTTON);
    }
}
