<?php

use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Controller\AbstractAdminApiController;
use PrestaShop\Module\Mbo\Api\Exception\IncompleteSignatureParamsException;
use PrestaShop\Module\Mbo\Api\Exception\QueryParamsException;
use PrestaShop\Module\Mbo\Api\Exception\RetrieveNewKeyException;
use PrestaShop\Module\Mbo\Api\Exception\UnauthorizedException;
use PrestaShop\Module\Mbo\Module\Command\ModuleStatusTransitionCommand;
use Tools;

/**
 * This controller is responsible to execute actions on modules installed on the current shop.
 * Caller have to be fully authenticated to perform actions given.
 */
class apiPsMboController extends AbstractAdminApiController
{
    public $type = Config::MODULE_ACTIONS;

    /**
     * @return void
     */
    public function postProcess()
    {
        $module = null;
        try {
            $transition = Tools::getValue('action');
            $moduleName = Tools::getValue('module');
            $source = Tools::getValue('source', null);

            if (empty($transition) || empty($moduleName)) {
                throw new QueryParamsException('You need transition and module parameters');
            }
            $command = new ModuleStatusTransitionCommand($transition, $moduleName, $source);

            /** @var \PrestaShop\Module\Mbo\Module\Module $module */
            $module = $this->module->get('mbo.modules.state_machine.module_status_transition_handler')->handle($command);

            $moduleUrls = $module->get('urls');
            $configUrl = (bool) $module->get('is_configurable') && isset($moduleUrls['configure']) ? $this->generateTokenizedModuleActionUrl($moduleUrls['configure']) : null;

            // Clear the cache after download to force reload module services
            $this->module->get('prestashop.adapter.cache.clearer.symfony_cache_clearer')->clear();
        } catch (\Exception $exception) {
            $this->exitWithExceptionMessage($exception);
        }

        $this->exitWithResponse([
            'message' => 'Transition successfully executed',
            'module_status' => $module->getStatus(),
            'version' => $module->get('version'),
            'config_url' => $configUrl,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function buildSignatureMessage(): string
    {
        // Payload elements
        $action = Tools::getValue('action');
        $module = Tools::getValue('module');
        $adminToken = Tools::getValue('admin_token');
        $actionUuid = Tools::getValue('action_uuid');

        if (
            !$action ||
            !$module ||
            !$adminToken ||
            !$actionUuid
        ) {
            throw new IncompleteSignatureParamsException('Expected signature elements are not given');
        }

        $keyVersion = Tools::getValue('version');

        return json_encode([
            'action' => $action,
            'module' => $module,
            'admin_token' => $adminToken,
            'action_uuid' => $actionUuid,
            'version' => $keyVersion,
        ]);
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
}
