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

namespace PrestaShop\Module\Mbo\Module\Workflow\Event;

use PrestaShop\Module\Mbo\Module\Workflow\TransitionsManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\WorkflowEvents;

final class TransitionEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var TransitionsManager
     */
    private $transitionsManager;

    public function __construct(TransitionsManager $transitionsManager)
    {
        $this->transitionsManager = $transitionsManager;
    }

    public function onWorkflowTransition(TransitionEvent $event)
    {
        $context = $event->getContext();

        // Get the transitionName, transform it to camelCase and give it to the markingStore via the context
        // The transformed transition name matches a method in the TransitionsManager class
        $transitionName = $event->getTransition()->getName();

        $context['transitionsManager'] = $this->transitionsManager;
        $context['method'] = (new UnicodeString($transitionName))->camel()->toString();

        $event->setContext($context);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkflowEvents::TRANSITION => 'onWorkflowTransition',
        ];
    }
}
