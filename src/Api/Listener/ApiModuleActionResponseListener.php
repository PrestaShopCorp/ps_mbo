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

namespace PrestaShop\Module\Mbo\Api\Listener;

use PrestaShop\Module\Mbo\Distribution\Client;
use PrestaShop\Module\Mbo\Module\Action\ActionInterface;
use PrestaShop\Module\Mbo\Module\Action\Scheduler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class ApiModuleActionResponseListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Scheduler
     */
    private $actionScheduler;

    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var Client
     */
    private $distributionApi;

    public function __construct(
        LoggerInterface $logger,
        Scheduler $actionScheduler,
        KernelInterface $kernel,
        Client $distributionApi
    ) {
        $this->logger = $logger;
        $this->actionScheduler = $actionScheduler;
        $this->kernel = $kernel;
        $this->distributionApi = $distributionApi;
    }

    public function onKernelTerminate(TerminateEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        $this->logger->info('Executing module_action post response');

        $actionToExecute = $this->getActionToExecute($request, $response);

        if (null !== $actionToExecute) {
            $this->logger->info('Launching command');

            $application = new Application($this->kernel);
            $application->setCatchExceptions(false);
            $application->setAutoExit(false);

            $input = new ArrayInput([
                'command' => 'prestashop:mbo:module-action-execute',
                'action-uuid' => $actionToExecute->getActionUuid(),
            ]);

            $output = new ConsoleOutput();

            try {
                $result = $application->run($input, $output);
            } catch(\Throwable $e){
                $errorMessage = $e->getMessage();
                // Notify Distribution API that error occured
                $this->distributionApi->notifyInstallError($actionToExecute, $errorMessage);
                $this->logger->critical(
                    sprintf(
                        'Install of module `%s` [action %s] failed with message : %s',
                        $actionToExecute->getModuleName(),
                        $actionToExecute->getActionUuid(),
                        $errorMessage
                    )
                );
            }

            // Logging command output for debugging purpose
//            $this->logger->debug($output->fetch());
        }
    }

    public function getActionToExecute(Request $request, Response $response): ?ActionInterface
    {
        // Do something only if we match the good action
        if ('api_mbo_module_action' !== $request->get('_route')) {
            return null;
        }
        $responseContent = $response->getContent();
        // If somehow response content is false or empty or not a string, we do nothing
        if (false === $responseContent || empty($responseContent) || !is_string($responseContent)) {
            return null;
        }

        $responseData = json_decode($responseContent, true);

        $actionUuid = $responseData['action_uuid'] ?? null;

        // If somehow actionUuid is not in the response, we do nothing
        if (null === $actionUuid) {
            return null;
        }

        $actionInPipe = $this->actionScheduler->getNextActionInQueue();

        // @TODO : Check for process request
        if (null !== $actionInPipe && $actionInPipe->getActionUuid() === $actionUuid) {
            return $actionInPipe;
        }
    }

}
