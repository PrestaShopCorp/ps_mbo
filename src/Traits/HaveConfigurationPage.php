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
use PrestaShop\Module\Mbo\Service\MboSymfonyCacheClearer;
use Symfony\Component\Dotenv\Dotenv;
use Tools;

trait HaveConfigurationPage
{
    private $environmentData = [
        'local' => [
            'cdc' => 'http://localhost:8080/dist/mbo-cdc.umd.js',
            'api' => 'http://localhost:3000',
            'addons' => 'https://preprod-api-addons.prestashop.com',
            'sentry_url' => '',
            'sentry_environment' => '',
        ],
        'prestabulle' => [
            'cdc' => 'https://integration-assets.prestashop3.com/dst/mbo/#prestabulle#/mbo-cdc.umd.js',
            'api' => 'https://mbo-api-#prestabulle#.prestashop.com',
            'addons' => 'https://addons-api-#pod#.prestashop.com',
            'sentry_url' => 'https://aa99f8a351b641af994ac50b01e14e20@o298402.ingest.sentry.io/6520457',
            'sentry_environment' => '#prestabulle#',
        ],
        'preprod' => [
            'cdc' => 'https://preproduction-assets.prestashop3.com/dst/mbo/v1/mbo-cdc.umd.js',
            'api' => 'https://mbo-api-preprod.prestashop.com',
            'addons' => 'https://preprod-api-addons.prestashop.com',
            'sentry_url' => 'https://aa99f8a351b641af994ac50b01e14e20@o298402.ingest.sentry.io/6520457',
            'sentry_environment' => 'preproduction',
        ],
        'prod' => [
            'cdc' => 'https://assets.prestashop3.com/dst/mbo/v1/mbo-cdc.umd.js',
            'api' => 'https://mbo-api.prestashop.com',
            'addons' => 'https://api-addons.prestashop.com',
            'sentry_url' => 'https://aa99f8a351b641af994ac50b01e14e20@o298402.ingest.sentry.io/6520457',
            'sentry_environment' => 'production',
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
        if (Tools::isSubmit('resetModule')) {
            try {
                $this->registerShop();
                $output .= '<b>Module is now well configured</b>';
            } catch (\Exception $e) {
                $output .= '<b>An error occurred, please reset the module manually in the module manager</b>';
            }
        }

        return $output;
    }

    private function saveNewDotenvData(string $envFilePath): string
    {
        // Get & build MBO env data
        $newMboValue = Tools::getValue('DISTRIBUTION_ENVIRONMENT');
        if (strpos($newMboValue, 'prestabulle') !== false) {
            $cdcUrl = str_replace('#prestabulle#', $newMboValue, $this->environmentData['prestabulle']['cdc']);
            $apiUrl = str_replace('#prestabulle#', $newMboValue, $this->environmentData['prestabulle']['api']);
            $sentryUrl = $this->environmentData['prestabulle']['sentry_url'];
            $sentryEnvironment = str_replace(
                '#prestabulle#',
                $newMboValue,
                $this->environmentData['prestabulle']['sentry_environment']
            );
        } else {
            $cdcUrl = $this->environmentData[$newMboValue]['cdc'];
            $apiUrl = $this->environmentData[$newMboValue]['api'];
            $sentryUrl = $this->environmentData[$newMboValue]['sentry_url'];
            $sentryEnvironment = $this->environmentData[$newMboValue]['sentry_environment'];
        }

        // Get & build Addons env data
        $newAddonsValue = Tools::getValue('ADDONS_ENVIRONMENT');
        if (strpos($newAddonsValue, 'pod') !== false) {
            $addonsUrl = str_replace('#pod#', $newAddonsValue, $this->environmentData['prestabulle']['addons']);
        } else {
            $addonsUrl = $this->environmentData[$newAddonsValue]['addons'];
        }

        // Build .env content
        $envData = file_get_contents($envFilePath);
        $envData = preg_replace('#MBO_CDC_URL=".*"#', 'MBO_CDC_URL="' . $cdcUrl . '"', $envData);
        $envData = preg_replace('#DISTRIBUTION_API_URL=".*"#', 'DISTRIBUTION_API_URL="' . $apiUrl . '"', $envData);
        $envData = preg_replace('#ADDONS_API_URL=".*"#', 'ADDONS_API_URL="' . $addonsUrl . '"', $envData);
        $envData = preg_replace('#SENTRY_CREDENTIALS=".*"#', 'SENTRY_CREDENTIALS="' . $sentryUrl . '"', $envData);
        $envData =
            preg_replace('#SENTRY_ENVIRONMENT=".*"#', 'SENTRY_ENVIRONMENT="' . $sentryEnvironment . '"', $envData);

        // Update the .env file
        file_put_contents($envFilePath, $envData);

        // Force reload of the .env file
        $dotenv = new Dotenv(true);
        $dotenv->overload($envFilePath);

        /** @var MboSymfonyCacheClearer $cacheClearer */
        $cacheClearer = $this->get(MboSymfonyCacheClearer::class);
        $cacheClearer->clear();

        $message = '<div style="padding-bottom: 15px;">Configuration updated to :
            <ul><li>MBO : ' . ucfirst($newMboValue) . '</li>
                <li>Addons : ' . ucfirst($newAddonsValue). '</li>
            </ul>';
        $message .= '<b>Don\'t forget to reset the module.</b><br />';
        $message .= '<form method="POST"><button type="submit" name="resetModule">Reset</button></form>';
        $message .= '</div>';

        return $message;
    }

    /**
     * Builds the configuration form
     *
     * @return string HTML code
     */
    public function displayForm(): string
    {
        $mboOptionsValues = [[
            'value' => 'local',
            'name' => 'Local',
        ]];

        for ($i = 1; $i < 10; ++$i) {
            $mboOptionsValues[] = [
                'value' => "prestabulle$i",
                'name' => "Prestabulle $i",
            ];
        }
        $mboOptionsValues = array_merge($mboOptionsValues, [
            [
                'value' => 'preprod',
                'name' => 'Preprod',
            ],
            [
                'value' => 'prod',
                'name' => 'Prod',
            ],
        ]);
        $addonsOptionsValues = [[
            'value' => 'local',
            'name' => 'Local',
        ]];

        for ($i = 1; $i < 10; ++$i) {
            $addonsOptionsValues[] = [
                'value' => "pod$i",
                'name' => "Pod $i",
            ];
        }
        $addonsOptionsValues = array_merge($addonsOptionsValues, [
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
                        'label' => $this->trans('MBO Configuration value', [], 'Admin.Global'),
                        'name' => 'DISTRIBUTION_ENVIRONMENT',
                        'options' => [
                            'id' => 'value',
                            'name' => 'name',
                            'query' => $mboOptionsValues,
                        ],
                        'required' => true,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Addons Configuration value', [], 'Admin.Global'),
                        'name' => 'ADDONS_ENVIRONMENT',
                        'options' => [
                            'id' => 'value',
                            'name' => 'name',
                            'query' => $addonsOptionsValues,
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
            $currentMboValue = $matches[1];
        } elseif (strpos($currentCdcUrl, 'preprod') !== false) {
            $currentMboValue = 'preprod';
        } elseif (strpos($currentCdcUrl, 'local') !== false) {
            $currentMboValue = 'local';
        } else {
            $currentMboValue = 'prod';
        }

        $helper->fields_value['DISTRIBUTION_ENVIRONMENT'] = $currentMboValue;

        $currentAddonsUrl = getenv('ADDONS_API_URL');

        if (strpos($currentAddonsUrl, 'pod') !== false) {
            preg_match('#(pod(?:\d+))#', $currentAddonsUrl, $matches);
            $currentAddonsValue = $matches[1];
        } elseif (strpos($currentAddonsUrl, 'preprod') !== false) {
            $currentAddonsValue = 'preprod';
        } elseif (strpos($currentAddonsUrl, 'local') !== false) {
            $currentAddonsValue = 'local';
        } else {
            $currentAddonsValue = 'prod';
        }

        $helper->fields_value['ADDONS_ENVIRONMENT'] = $currentAddonsValue;

        return $helper->generateForm([$form]);
    }
}
