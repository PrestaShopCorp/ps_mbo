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

use Exception;
use PrestaShop\Module\Mbo\Module\Exception\TransitionFailedException;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @see \Symfony\Component\Workflow\MarkingStore\MethodMarkingStore
 */
final class MarkingStore implements MarkingStoreInterface
{
    private $singleState;
    private $property;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param string $property Used to determine methods to call
     *                         The `getMarking` method will use `$subject->getProperty()`
     *                         The `setMarking` method will use `$subject->setProperty(string|array $places, array $context = array())`
     */
    public function __construct(TranslatorInterface $translator, bool $singleState = false, string $property = 'marking')
    {
        $this->singleState = $singleState;
        $this->property = $property;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getMarking($subject): Marking
    {
        $method = 'get' . ucfirst($this->property);

        if (!method_exists($subject, $method)) {
            throw new LogicException(sprintf('The method "%s::%s()" does not exist.', \get_class($subject), $method));
        }

        $marking = null;
        try {
            $marking = $subject->{$method}();
        } catch (\Error $e) {
            $unInitializedPropertyMassage = sprintf('Typed property %s::$%s must not be accessed before initialization', get_debug_type($subject), $this->property);
            if ($e->getMessage() !== $unInitializedPropertyMassage) {
                throw $e;
            }
        }

        if (null === $marking) {
            return new Marking();
        }

        if ($this->singleState) {
            $marking = [(string) $marking => 1];
        }

        return new Marking($marking);
    }

    /**
     * {@inheritdoc}
     */
    public function setMarking($subject, Marking $marking, array $context = [])
    {
        $marking = $marking->getPlaces();

        if ($this->singleState) {
            $marking = key($marking);
        }

        $method = 'set' . ucfirst($this->property);

        // Use the method defined in the context to perform transition
        if (isset($context['method'])) {
            $transitionMethod = $context['method'];

            if (
                isset($context['transitionsManager']) &&
                method_exists($context['transitionsManager'], $transitionMethod)
            ) {
                try {
                    if (!$context['transitionsManager']->{$transitionMethod}($subject, $marking, $context)) {
                        throw new Exception($this->translator->trans('Unfortunately, the module did not return additional details.', [], 'Admin.Modules.Notification'));
                    }
                } catch (Exception $e) {
                    throw new TransitionFailedException(sprintf('Unable to execute transition : %s', $e->getMessage()), 0, $e);
                }

                return;
            }
        }

        if (!method_exists($subject, $method)) {
            throw new LogicException(sprintf('The method "%s::%s()" does not exist.', \get_class($subject), $method));
        }

        $subject->{$method}($marking, $context);
    }
}
