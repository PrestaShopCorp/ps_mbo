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

namespace PrestaShop\Module\Mbo\RecommendedModule;

use ArrayIterator;
use Closure;

class RecommendedModuleCollection implements RecommendedModuleCollectionInterface
{
    /**
     * @var RecommendedModuleInterface[]
     */
    protected $recommendedModules = [];

    /**
     * {@inheritdoc}
     */
    public function addRecommendedModule(RecommendedModuleInterface $recommendedModule): RecommendedModuleCollectionInterface
    {
        $this->recommendedModules[] = $recommendedModule;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->recommendedModules);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): RecommendedModuleInterface
    {
        return $this->recommendedModules[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->recommendedModules[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->recommendedModules[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->recommendedModules);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->recommendedModules);
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        return empty($this->recommendedModules);
    }

    /**
     * {@inheritdoc}
     */
    public function sortByPosition()
    {
        $this->sort(function (
            RecommendedModuleInterface $recommendedModuleA,
            RecommendedModuleInterface $recommendedModuleB
        ) {
            if ($recommendedModuleA->getPosition() === $recommendedModuleB->getPosition()) {
                return 0;
            }

            return ($recommendedModuleA->getPosition() < $recommendedModuleB->getPosition()) ? -1 : 1;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getInstalled(): RecommendedModuleCollectionInterface
    {
        return $this->filter(function (RecommendedModuleInterface $recommendedModule) {
            return $recommendedModule->isInstalled();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getNotInstalled(): RecommendedModuleCollectionInterface
    {
        return $this->filter(function (RecommendedModuleInterface $recommendedModule) {
            return !$recommendedModule->isInstalled();
        });
    }

    /**
     * @param Closure $closure
     *
     * @return RecommendedModuleCollection
     */
    protected function filter(Closure $closure): RecommendedModuleCollectionInterface
    {
        $recommendedModules = new self();
        $recommendedModules->recommendedModules = array_filter(
            $this->recommendedModules,
            $closure,
            ARRAY_FILTER_USE_BOTH
        );
        $recommendedModules->sortByPosition();

        return $recommendedModules;
    }

    /**
     * @param Closure $closure
     */
    protected function sort(Closure $closure)
    {
        uasort(
            $this->recommendedModules,
            $closure
        );
    }
}
