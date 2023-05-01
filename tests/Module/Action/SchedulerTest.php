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

namespace PrestaShop\Module\Mbo\Tests\Module\Action;

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Mbo\Distribution\Client;
use PrestaShop\Module\Mbo\Module\Action\ActionBuilder;
use PrestaShop\Module\Mbo\Module\Action\ActionInterface;
use PrestaShop\Module\Mbo\Module\Action\ActionRetriever;
use PrestaShop\Module\Mbo\Module\Action\InstallAction;
use PrestaShop\Module\Mbo\Module\Action\Scheduler;
use PrestaShop\Module\Mbo\Module\Repository;
use PrestaShop\PrestaShop\Core\Module\ModuleManager;

class SchedulerTest extends TestCase
{
    protected function setUp(): void
    {

    }

    public function testGetNextActionInQueueWhenActionsAreEmpty()
    {
        $actionRetriever = $this->createMock(ActionRetriever::class);

        $actionRetriever
            ->expects($this->once())
            ->method('getActionsInDb')
            ->willReturn([])
        ;

        $scheduler = new Scheduler($actionRetriever);

        $this->assertNull($scheduler->getNextActionInQueue());
    }

    public function testGetNextActionInQueueWhenAnInProgressActionExists()
    {
        $moduleManager = $this->createMock(ModuleManager::class);
        $repository = $this->createMock(Repository::class);
        $distributionApi = $this->createMock(Client::class);
        $actionRetriever = $this->createMock(ActionRetriever::class);

        $action1 = new InstallAction($moduleManager, $repository, $distributionApi, '1234', 'module_one', null, ActionInterface::PROCESSED);
        $action2 = new InstallAction($moduleManager, $repository, $distributionApi, '1235', 'module_two', null, ActionInterface::PENDING);
        $action3 = new InstallAction($moduleManager, $repository, $distributionApi, '1236', 'module_three', null, ActionInterface::PROCESSED);
        $action4 = new InstallAction($moduleManager, $repository, $distributionApi, '1237', 'module_four', null, ActionInterface::PROCESSING);
        $action5 = new InstallAction($moduleManager, $repository, $distributionApi, '1238', 'module_five', null, ActionInterface::PROCESSED);
        $action6 = new InstallAction($moduleManager, $repository, $distributionApi, '1239', 'module_six', null, ActionInterface::PENDING);

        $actionRetriever
            ->expects($this->once())
            ->method('getActionsInDb')
            ->willReturn([
                $action1,
                $action2,
                $action3,
                $action4,
                $action5,
                $action6,
            ])
        ;

        $scheduler = new Scheduler($actionRetriever);

        $this->assertSame($action4, $scheduler->getNextActionInQueue());
    }

    public function testGetNextActionInQueueWhenNoInProgressActionExists()
    {
        $moduleManager = $this->createMock(ModuleManager::class);
        $repository = $this->createMock(Repository::class);
        $distributionApi = $this->createMock(Client::class);
        $actionRetriever = $this->createMock(ActionRetriever::class);

        $action1 = new InstallAction($moduleManager, $repository, $distributionApi, '1234', 'module_one', null, ActionInterface::PROCESSED);
        $action2 = new InstallAction($moduleManager, $repository, $distributionApi, '1235', 'module_two', null, ActionInterface::PENDING);
        $action3 = new InstallAction($moduleManager, $repository, $distributionApi, '1236', 'module_three', null, ActionInterface::PROCESSED);
        $action4 = new InstallAction($moduleManager, $repository, $distributionApi, '1237', 'module_four', null, ActionInterface::PENDING);
        $action5 = new InstallAction($moduleManager, $repository, $distributionApi, '1238', 'module_five', null, ActionInterface::PROCESSED);
        $action6 = new InstallAction($moduleManager, $repository, $distributionApi, '1239', 'module_six', null, ActionInterface::PENDING);

        $actionRetriever
            ->expects($this->once())
            ->method('getActionsInDb')
            ->willReturn([
                $action2,
                $action4,
                $action5,
            ])
        ;

        $scheduler = new Scheduler($actionRetriever);

        $this->assertSame($action2, $scheduler->getNextActionInQueue());
    }

    public function testProcessNextActionInQueueWhenActionsAreEmpty()
    {
        $actionRetriever = $this->createMock(ActionRetriever::class);

        $actionRetriever
            ->expects($this->once())
            ->method('getActionsInDb')
            ->willReturn([])
        ;

        $scheduler = new Scheduler($actionRetriever);

        $this->assertSame(Scheduler::NO_ACTION_IN_QUEUE, $scheduler->processNextAction());
    }

    public function testProcessNextActionInQueueWhenAnInProgressActionExists()
    {
        $moduleManager = $this->createMock(ModuleManager::class);
        $repository = $this->createMock(Repository::class);
        $distributionApi = $this->createMock(Client::class);
        $actionRetriever = $this->createMock(ActionRetriever::class);

        $action1 = new InstallAction($moduleManager, $repository, $distributionApi, '1234', 'module_one', null, ActionInterface::PROCESSED);
        $action2 = new InstallAction($moduleManager, $repository, $distributionApi, '1235', 'module_two', null, ActionInterface::PENDING);
        $action3 = new InstallAction($moduleManager, $repository, $distributionApi, '1236', 'module_three', null, ActionInterface::PROCESSED);
        $action4 = new InstallAction($moduleManager, $repository, $distributionApi, '1237', 'module_four', null, ActionInterface::PROCESSING);
        $action5 = new InstallAction($moduleManager, $repository, $distributionApi, '1238', 'module_five', null, ActionInterface::PROCESSED);
        $action6 = new InstallAction($moduleManager, $repository, $distributionApi, '1239', 'module_six', null, ActionInterface::PENDING);

        $actionRetriever
            ->expects($this->once())
            ->method('getActionsInDb')
            ->willReturn([
                $action1,
                $action2,
                $action3,
                $action4,
                $action5,
                $action6,
            ])
        ;

        $scheduler = new Scheduler($actionRetriever);

        $this->assertSame(Scheduler::ACTION_ALREADY_PROCESSING, $scheduler->processNextAction());
    }

    public function testProcessNextActionInQueueWhenNoInProgressActionExists()
    {
        $moduleManager = $this->createMock(ModuleManager::class);
        $repository = $this->createMock(Repository::class);
        $distributionApi = $this->createMock(Client::class);
        $actionRetriever = $this->createPartialMock(ActionRetriever::class, array('getActionsInDb', 'markActionAsProcessing'));

        $action1 = new InstallAction($moduleManager, $repository, $distributionApi, '1234', 'module_one', null, ActionInterface::PROCESSED);
        $action2 = new InstallAction($moduleManager, $repository, $distributionApi, '1235', 'module_two', null, ActionInterface::PENDING);
        $action3 = new InstallAction($moduleManager, $repository, $distributionApi, '1236', 'module_three', null, ActionInterface::PROCESSED);
        $action4 = new InstallAction($moduleManager, $repository, $distributionApi, '1237', 'module_four', null, ActionInterface::PENDING);
        $action5 = new InstallAction($moduleManager, $repository, $distributionApi, '1238', 'module_five', null, ActionInterface::PROCESSED);
        $action6 = new InstallAction($moduleManager, $repository, $distributionApi, '1239', 'module_six', null, ActionInterface::PENDING);

        $actionRetriever
            ->expects($this->once())
            ->method('getActionsInDb')
            ->willReturn([
                $action2,
                $action4,
                $action6,
            ])
        ;

        $actionRetriever
            ->expects($this->once())
            ->method('markActionAsProcessing')
            ->willReturnArgument(0)
        ;

        $scheduler = new Scheduler($actionRetriever);

        $processResult = $scheduler->processNextAction();
        $this->assertInstanceOf(ActionInterface::class, $processResult);
        $this->assertSame($action2->getActionUuid(), $processResult->getActionUuid());
    }
}
