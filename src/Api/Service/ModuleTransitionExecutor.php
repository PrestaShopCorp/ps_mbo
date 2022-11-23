<?php

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
            $psMbo->get('prestashop.adapter.cache.clearer.symfony_cache_clearer')->clear();
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
        $addonsCredentials = Tools::getValue('addons_credentials', null);

        if (null === $addonsCredentials) {
            return;
        }

        $credentials = json_decode($addonsCredentials, true);

        if (!is_array($credentials) || !isset($credentials['addons_username']) || !isset($credentials['addons_pwd'])) {
            return;
        }

        $session->set('username_addons', $credentials['addons_username']);
        $session->set('password_addons', $credentials['addons_pwd']);
        $session->set('is_contributor', '0');
    }
}
