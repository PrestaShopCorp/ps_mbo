<?php

use PrestaShop\Module\Mbo\Api\Config\Config;
use PrestaShop\Module\Mbo\Api\Controller\AbstractAdminApiController;
use PrestaShop\Module\Mbo\Api\Exception\QueryParamsException;
use PrestaShop\Module\Mbo\Module\Command\ModuleStatusTransitionCommand;

class apiPsMboController extends AbstractAdminApiController
{
    public $type = Config::MODULE_ACTIONS;

    /**
     * @return void
     */
    public function postProcess()
    {
        try {
            $transition = Tools::getValue('action');
            $moduleName = Tools::getValue('module');
            $source = Tools::getValue('source', null);

            if (empty($transition) || empty($moduleName)) {
                throw new QueryParamsException('You need transition and module parameters');
            }
            $command = new ModuleStatusTransitionCommand($transition, $moduleName, $source);
            /**
             * @var \PrestaShop\Module\Mbo\Module\Module $module
             */
            $module = $this->module->get('mbo.modules.state_machine.module_status_transition_handler')->handle($command);
        } catch (\Exception $exception) {
            $this->exitWithExceptionMessage($exception);
        }

        $moduleUrls = $module->get('urls');
        $configUrl = (bool) $module->get('is_configurable') && isset($moduleUrls['configure']) ? $moduleUrls['configure'] : null;
        $configUrl = $this->generateTokenizedModuleActionUrl($configUrl);

        $this->exitWithResponse([
            'message' => $this->trans('Transition successfully executed'),
            'module_status' => $module->getStatus(),
            'version' => $module->get('version'),
            'config_url' => $configUrl,
        ]);
    }

    private function generateTokenizedModuleActionUrl($url)
    {
        $components = parse_url($url);
        $baseUrl = ($components['path'] ?? '');
        $queryParams = [];
        if (isset($components['query'])) {
            $query = $components['query'];

            parse_str($query, $queryParams);
        }

        if(!isset($queryParams['_token'])) {
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
