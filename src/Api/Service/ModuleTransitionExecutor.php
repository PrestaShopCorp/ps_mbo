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

namespace PrestaShop\Module\Mbo\Api\Service;

use http\Exception\InvalidArgumentException;
use PrestaShop\Module\Mbo\Api\Exception\QueryParamsException;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Module\Command\ModuleStatusTransitionCommand;
use PrestaShop\Module\Mbo\Module\CommandHandler\ModuleStatusTransitionCommandHandler;
use PrestaShop\Module\Mbo\Module\Exception\ModuleNewVersionNotFoundException;
use PrestaShop\Module\Mbo\Module\Exception\ModuleNotFoundException;
use PrestaShop\Module\Mbo\Module\Exception\TransitionCommandToModuleStatusException;
use PrestaShop\Module\Mbo\Module\Exception\TransitionFailedException;
use PrestaShop\Module\Mbo\Module\Exception\UnauthorizedModuleTransitionException;
use PrestaShop\Module\Mbo\Module\Exception\UnexpectedModuleSourceContentException;
use PrestaShop\Module\Mbo\Module\Exception\UnknownModuleTransitionCommandException;
use PrestaShop\Module\Mbo\Module\Module;
use PrestaShop\Module\Mbo\Module\ValueObject\ModuleTransitionCommand;
use PrestaShop\PrestaShop\Core\Cache\Clearer\CacheClearerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Tools;

class ModuleTransitionExecutor implements ServiceExecutorInterface
{
    const SERVICE = 'module';

    /**
     * @var ModuleStatusTransitionCommandHandler
     */
    private $moduleStatusTransitionCommandHandler;

    public function __construct(ModuleStatusTransitionCommandHandler $moduleStatusTransitionCommandHandler)
    {
        $this->moduleStatusTransitionCommandHandler = $moduleStatusTransitionCommandHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function canExecute(string $service): bool
    {
        return self::SERVICE === $service;
    }

    /**
     * {@inheritDoc}
     *
     * @throws UnknownModuleTransitionCommandException
     * @throws QueryParamsException
     * @throws ModuleNewVersionNotFoundException
     * @throws ModuleNotFoundException
     * @throws TransitionCommandToModuleStatusException
     * @throws TransitionFailedException
     * @throws UnauthorizedModuleTransitionException
     * @throws UnexpectedModuleSourceContentException
     * @throws \Exception
     */
    public function execute(...$parameters): array
    {
        if (!$parameters[0] instanceof \Module) {
            throw new InvalidArgumentException();
        }

        $psMbo = $parameters[0];

        $transition = Tools::getValue('action');
        $moduleName = Tools::getValue('module');
        $moduleId = (int) Tools::getValue('module_id');
        $moduleVersion = Tools::getValue('module_version');
        $source = Tools::getValue('source', null);

        if (empty($transition) || empty($moduleName)) {
            throw new QueryParamsException('You need transition and module parameters');
        }

        try {
            $session = $psMbo->get('session');
            if (!$session instanceof Session) {
                throw new \Exception('ModuleTransitionExecutor : Session not found');
            }
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);
            throw $e;
        }
        // Authenticate user to addons if credentials are provided
        $this->authenticateAddonsUser($session);

        $command = new ModuleStatusTransitionCommand($transition, $moduleName, $moduleId, $moduleVersion, $source);

        $module = $this->moduleStatusTransitionCommandHandler->handle($command);

        $moduleUrls = $module->get('urls');
        $configUrl = $module->get('is_configurable') && isset($moduleUrls['configure'])
            ? $this->generateTokenizedModuleActionUrl($moduleUrls['configure'])
            : null;

        if (ModuleTransitionCommand::MODULE_COMMAND_DOWNLOAD === $transition) {
            // Clear the cache after download to force reload module services
            try {
                /** @var CacheClearerInterface $cacheClearer */
                $cacheClearer = $psMbo->get('mbo.symfony_cache_clearer');
            } catch (\Exception $e) {
                ErrorHelper::reportError($e);
                $cacheClearer = false;
            }
            if ($cacheClearer) {
                $cacheClearer->clear();
            }
        }

        return [
            'message' => 'Transition successfully executed',
            'module_status' => $module->getStatus(),
            'version' => $module->get('version'),
            'config_url' => $configUrl,
        ];
    }

    private function generateTokenizedModuleActionUrl($url): string
    {
        $components = parse_url($url);
        $baseUrl = ($components['path'] ?? '');

        $composedUrl = '';
        if (!empty($components['scheme'])) {
            $scheme = $components['scheme'];
            $composedUrl .= $scheme . ':';
        }

        if (!empty($components['host'])) {
            $composedUrl .= '//';
            if (isset($components['user'])) {
                $composedUrl .= $components['user'];
                if (isset($components['pass'])) {
                    $composedUrl .= ':' . $components['pass'];
                }
                $composedUrl .=  '@';
            }

            $composedUrl .= $components['host'];

            // Only include the port if it is not the default port of the scheme
            if (isset($components['port'])) {
                $composedUrl .= ':' . $components['port'];
            }
        }

        $composedUrl .= $baseUrl;

        $queryParams = [];
        if (is_array($components) && isset($components['query']) && is_string($components['query'])) {
            parse_str($components['query'], $queryParams);
        }

        if (!isset($queryParams['_token'])) {
            return $composedUrl;
        }

        $adminToken = Tools::getValue('admin_token');
        $queryParams['_token'] = $adminToken;

        $composedUrl .=  '?' . http_build_query($queryParams, '', '&');
        if (isset($components['fragment']) && $components['fragment'] !== '') {
            /* This copy-paste from Symfony's UrlGenerator */
            $composedUrl .= '#' . strtr(rawurlencode($components['fragment']), ['%2F' => '/', '%3F' => '?']);
        }

        return $composedUrl;
    }

    private function authenticateAddonsUser(Session $session): void
    {
        // If we receive an accounts_token, we use it to connect to addons
        $accountsToken = Tools::getValue('accounts_token', null);

        if (null !== $accountsToken) {
            $session->set('accounts_token', $accountsToken);
        }
    }
}
