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
use PrestaShop\Module\Mbo\Module\Command\ModuleStatusTransitionCommand;
use PrestaShop\Module\Mbo\Module\CommandHandler\ModuleStatusTransitionCommandHandler;
use PrestaShop\Module\Mbo\Module\ValueObject\ModuleTransitionCommand;
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
     */
    public function execute(...$parameters): array
    {
        if (!$parameters[0] instanceof \Module) {
            throw new InvalidArgumentException();
        }

        $psMbo = $parameters[0];

        $transition = Tools::getValue('action');
        $moduleName = Tools::getValue('module');
        $source = Tools::getValue('source', null);

        if (empty($transition) || empty($moduleName)) {
            throw new QueryParamsException('You need transition and module parameters');
        }

        // Authenticate user to addons if credentials are provided
        $this->authenticateAddonsUser($psMbo->get('session'));

        $command = new ModuleStatusTransitionCommand($transition, $moduleName, $source);

        /** @var \PrestaShop\Module\Mbo\Module\Module $module */
        $module = $this->moduleStatusTransitionCommandHandler->handle($command);

        $moduleUrls = $module->get('urls');
        $configUrl = (bool) $module->get('is_configurable') && isset($moduleUrls['configure']) ? $this->generateTokenizedModuleActionUrl($moduleUrls['configure']) : null;

        if (ModuleTransitionCommand::MODULE_COMMAND_DOWNLOAD === $transition) {
            // Clear the cache after download to force reload module services
            try {
                /** @var \PrestaShop\PrestaShop\Adapter\Cache\Clearer\SymfonyCacheClearer $cacheClearer */
                $cacheClearer = $psMbo->get('prestashop.adapter.cache.clearer.symfony_cache_clearer');
            } catch (\Exception $e) {
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
        $queryParams = [];
        if (isset($components['query'])) {
            $query = $components['query'];

            parse_str($query, $queryParams);
        }

        if (!isset($queryParams['_token'])) {
            return $url;
        }

        $adminToken = Tools::getValue('admin_token');
        $queryParams['_token'] = $adminToken;

        $url = $baseUrl . '?' . http_build_query($queryParams, '', '&');
        if (isset($components['fragment']) && $components['fragment'] !== '') {
            /* This copy-paste from Symfony's UrlGenerator */
            $url .= '#' . strtr(rawurlencode($components['fragment']), ['%2F' => '/', '%3F' => '?']);
        }

        return $url;
    }

    private function authenticateAddonsUser(Session $session): void
    {
        // If we receive an accounts_token, we use it to connect to addons
        $accountsToken = Tools::getValue('accounts_token', null);

        if (null !== $accountsToken) {
            $session->set('accounts_token', $accountsToken);

            return;
        }

        // If we don't have accounts_token, we try to connect with addons credentials
        $addonsUsername = Tools::getValue('addons_username', null);
        $addonsPwd = Tools::getValue('addons_pwd', null);

        if (!isset($addonsUsername) || !isset($addonsPwd)) {
            return;
        }

        $session->set('username_addons_v2', $addonsUsername);
        $session->set('password_addons_v2', $addonsPwd);
        $session->set('is_contributor', '0');
    }
}
