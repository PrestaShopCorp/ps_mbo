<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace PrestaShop\Module\Mbo\Command;

use Exception;
use Module;
use PrestaShop\Module\Mbo\Api\Service\Factory as ExcutorsFactory;
use PrestaShop\Module\Mbo\Api\Service\ModuleActionExecutor;
use PrestaShop\Module\Mbo\Module\Action\ActionInterface;
use PrestaShop\Module\Mbo\Module\Action\ActionRetriever;
use PrestaShop\PrestaShop\Adapter\CoreException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ModuleActionCommand extends Command
{
    /**
     * @var ActionRetriever
     */
    private $actionRetriever;
    /**
     * @var ExcutorsFactory
     */
    private $executorsFactory;
    /**
     * @var \Module
     */
    private $module;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var FormatterHelper
     */
    private $formatter;
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(
        ActionRetriever $actionRetriever,
        ExcutorsFactory $executorsFactory,
        Module $module,
        TranslatorInterface $translator
    ) {
        parent::__construct();
        $this->actionRetriever = $actionRetriever;
        $this->executorsFactory = $executorsFactory;
        $this->module = $module;
        $this->translator = $translator;
    }

    protected function configure()
    {
        $this
            ->setName('prestashop:mbo:module-action-execute')
            ->setDescription('Executes a module action stored in the queue')
            ->addArgument('action-uuid', InputArgument::REQUIRED, 'UUID of the action to execute.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        header("HTTP/1.1 500 Internal Server Error", true, 500);
        echo 'cinq cent';
        return 1;
//        throw new CoreException('Hey');
        $this->input = $input;
        $this->output = $output;
        $this->formatter = $this->getHelper('formatter');
        $this->displayMessage('Executing a module action stored in the Queue');

        $actionUuid = $input->getArgument('action-uuid');

        if (!is_string($actionUuid)) {
            $this->displayMessage(
                $this->translator->trans('Action UUID is invalid', [], 'Admin.Modules.Notification'),
                'error'
            );

            return 1;
        }

        $action = $this->actionRetriever->getActionByUuid($actionUuid);

        if (!$action instanceof ActionInterface) {
            $this->displayMessage(
                $this->translator->trans('No action found with the given UUID', [], 'Admin.Modules.Notification'),
                'error'
            );

            return 1;
        }

        if ($action->isProcessed()) {
            $this->displayMessage(
                $this->translator->trans('Given action is already processed', [], 'Admin.Modules.Notification'),
                'comment'
            );

            return 1;
        }

        $processingAction = $this->actionRetriever->getProcessingAction();

        if (null === $processingAction) { // No action is in progress
            $this->actionRetriever->markActionAsProcessing($action);
        } elseif ($processingAction->getActionUuid() !== $action->getActionUuid()) {
            $this->displayMessage(
                'Another action is already processing',
                'error'
            );

            return 1;
        }

        $this->displayMessage('Executing module action with UUID [' . $actionUuid . ']');

//        try {
            $this->executorsFactory->build(ModuleActionExecutor::SERVICE)->execute($this->module, $action);
//        } catch(Exception $e) {
//            $this->displayMessage(
//                sprintf('An error has been thrown when executing module action %s : %s', $actionUuid, $e->getMessage())
//            );
//
//            return 1;
//        }

        return 0;
    }

    protected function displayMessage($message, $type = 'info')
    {
        $this->output->writeln(
            $this->formatter->formatBlock($message, $type, true)
        );
    }
}
