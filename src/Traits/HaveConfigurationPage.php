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

namespace PrestaShop\Module\Mbo\Traits;

use AdminController;
use Configuration;
use HelperForm;
use Symfony\Component\Dotenv\Dotenv;
use Tools;

trait HaveConfigurationPage
{
    private $environmentData = [
        'local' => [
            'cdc' => 'http://localhost:8080/mbo-cdc.umd.js',
            'api' => 'http://localhost:3000',
            'addons' => 'https://preprod-api-addons.prestashop.com',
        ],
        'prestabulle' => [
            'cdc' => 'https://integration-assets.prestashop3.com/dst/mbo/#prestabulle#/mbo-cdc.umd.js',
            'api' => 'https://mbo-api-#prestabulle#.prestashop.com',
            'addons' => 'https://preprod-api-addons.prestashop.com',
        ],
        'preprod' => [
            'cdc' => 'https://preproduction-assets.prestashop3.com/dst/mbo/v1/mbo-cdc.umd.js',
            'api' => 'https://mbo-api-preprod.prestashop.com',
            'addons' => 'https://preprod-api-addons.prestashop.com',
        ],
        'prod' => [
            'cdc' => 'https://assets.prestashop3.com/dst/mbo/v1/mbo-cdc.umd.js',
            'api' => 'https://mbo-api.prestashop.com',
            'addons' => 'https://api-addons.prestashop.com',
        ],
    ];

    /**
     * This method handles the module's configuration page
     *
     * @return string The page's HTML content
     */
    public function getContent(): string
    {
        $output = $this->handleSaveForm();

        return $output . $this->displayForm();
    }

    private function handleSaveForm(): string
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            $possibleEnvFiles = [
                '.env',
                '.env.local',
                '.env.dist',
            ];

            $correctEnvFile = false;
            foreach ($possibleEnvFiles as $envFile) {
                $envFilePath = $this->getLocalPath() . $envFile;
                if (file_exists($envFilePath)) {
                    $correctEnvFile = true;
                    break;
                }
            }
            if ($correctEnvFile) {
                $output .= $this->saveNewDotenvData($envFilePath);
            }
        }

        return $output;
    }

    private function saveNewDotenvData(string $envFilePath): string
    {
        $newValue = Tools::getValue('DISTRIBUTION_ENVIRONMENT');

        if (strpos($newValue, 'prestabulle') !== false) {
            $cdcUrl = str_replace('#prestabulle#', $newValue, $this->environmentData['prestabulle']['cdc']);
            $apiUrl = str_replace('#prestabulle#', $newValue, $this->environmentData['prestabulle']['api']);
            $addonsUrl = str_replace('#prestabulle#', $newValue, $this->environmentData['prestabulle']['addons']);
        } else {
            $cdcUrl = $this->environmentData[$newValue]['cdc'];
            $apiUrl = $this->environmentData[$newValue]['api'];
            $addonsUrl = $this->environmentData[$newValue]['addons'];
        }

        $envData = file_get_contents($envFilePath);
        $envData = preg_replace('#MBO_CDC_URL=".*"#', 'MBO_CDC_URL="' . $cdcUrl . '"', $envData);
        $envData = preg_replace('#DISTRIBUTION_API_URL=".*"#', 'DISTRIBUTION_API_URL="' . $apiUrl . '"', $envData);
        $envData = preg_replace('#ADDONS_API_URL=".*"#', 'ADDONS_API_URL="' . $addonsUrl . '"', $envData);

        // Update the .env file
        file_put_contents($envFilePath, $envData);

        // Force reload of the .env file
        $dotenv = new Dotenv(true);
        $dotenv->overload($envFilePath);

        return 'Configuration updated to <b>' . ucfirst($newValue) . "</b>.Don't forget to reset the module.";
    }

    /**
     * Builds the configuration form
     *
     * @return string HTML code
     */
    public function displayForm(): string
    {
        $options = [[
            'value' => 'local',
            'name' => 'Local',
        ]];

        for ($i = 1; $i < 10; ++$i) {
            $options[] = [
                'value' => "prestabulle$i",
                'name' => "Prestabulle $i",
            ];
        }
        $options = array_merge($options, [
            [
                'value' => 'preprod',
                'name' => 'Preprod',
            ],
            [
                'value' => 'prod',
                'name' => 'Prod',
            ],
        ]);

        // Init Fields form array
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->trans('Configuration value', [], 'Admin.Global'),
                        'name' => 'DISTRIBUTION_ENVIRONMENT',
                        'options' => [
                            'id' => 'value',
                            'name' => 'name',
                            'query' => $options,
                        ],
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Global'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        $currentCdcUrl = getenv('MBO_CDC_URL');

        if (strpos($currentCdcUrl, 'prestabulle') !== false) {
            preg_match('#(prestabulle(?:\d+))#', $currentCdcUrl, $matches);
            $currentValue = $matches[1];
        } elseif (strpos($currentCdcUrl, 'preprod') !== false) {
            $currentValue = 'preprod';
        } elseif (strpos($currentCdcUrl, 'local') !== false) {
            $currentValue = 'local';
        } else {
            $currentValue = 'prod';
        }

        $helper->fields_value['DISTRIBUTION_ENVIRONMENT'] = $currentValue;

        return $helper->generateForm([$form]);
    }
}
