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

namespace PrestaShop\Module\Mbo\Traits\Hooks;

use Exception;
use PrestaShop\Module\Mbo\Exception\ExpectedServiceNotFoundException;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Module\CollectionFactory;
use PrestaShop\Module\Mbo\Module\Filters;
use PrestaShop\Module\Mbo\Module\FiltersFactory;
use PrestaShop\Module\Mbo\Module\Repository;
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

trait UseActionListModules
{
    /**
     * Hook displayModuleConfigureExtraButtons.
     * Add additional buttons on the module configure page's toolbar.
     *
     * @return array<array<string, string>>
     *
     * @throws Exception
     */
    public function hookActionListModules(): array
    {
        try {
            /** @var FiltersFactory $filtersFactory */
            $filtersFactory = $this->get('mbo.modules.filters.factory');
            /** @var CollectionFactory $collectionFactory */
            $collectionFactory = $this->get('mbo.modules.collection.factory');
            /** @var Repository $moduleRepository */
            $moduleRepository = $this->get('mbo.modules.repository');
            /** @var Router $router */
            $router = $this->get('router');

            if (
                null === $filtersFactory
                || null === $collectionFactory
                || null === $moduleRepository
                || null === $router
            ) {
                throw new ExpectedServiceNotFoundException('Some services not found in UseActionListModules');
            }
        } catch (Exception $exception) {
            ErrorHelper::reportError($exception);

            return [];
        }
        $filters = $filtersFactory->create();
        $filters
            ->setType(Filters\Type::MODULE | Filters\Type::SERVICE)
            ->setStatus(Filters\Status::ALL & Filters\Status::INSTALLED);

        $modulesCollection = $collectionFactory->build(
            $moduleRepository->fetchAll(),
            $filters
        );

        $shopUrl = Config::getShopUrl();
        $modules = [];

        $catalogUrl = $router->generate('admin_mbo_catalog_module', []);
        $catalogUrlParts = parse_url($catalogUrl);
        $catalogUrlParams = [];

        if (is_array($catalogUrlParts) && isset($catalogUrlParts['query']) && is_string($catalogUrlParts['query'])) {
            parse_str($catalogUrlParts['query'], $catalogUrlParams);
        }

        /**
         * @var ModuleInterface $module
         */
        foreach ($modulesCollection as $name => $module) {
            $downloadUrl = $module->get('download_url');
            if (is_string($downloadUrl) && strpos($downloadUrl, 'shop_url') === false) {
                $downloadUrl .= '&shop_url=' . $shopUrl;
            }

            if ('ps_mbo' === $name) {
                $downloadUrl = null;
            }

            $catalogUrlParams['mbo_cdc_path'] = sprintf('/#/module/%d/fullpage', (int) $module->get('id'));
            $catalogUrlParts['query'] = http_build_query($catalogUrlParams);

            $modules[] = [
                'name' => $name,
                'displayName' => $module->get('displayName'),
                'description' => $module->get('description'),
                'additional_description' => $this->getAdditionalDescription(http_build_url($catalogUrlParts), $name),
                'version' => (string) $module->get('version'),
                'version_available' => $module->get('version_available'),
                'author' => $module->get('author'),
                'url' => $module->get('url'),
                'download_url' => $downloadUrl,
                'img' => $module->get('img'),
                'tab' => $module->get('tab'),
            ];
        }

        return $modules;
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function getAdditionalDescription(string $moduleUrl, string $moduleName): string
    {
        try {
            /** @var Environment $twigEnvironment */
            $twigEnvironment = $this->get('twig');

            if (null === $twigEnvironment) {
                throw new ExpectedServiceNotFoundException('Unable to get Twig service');
            }
        } catch (Exception $exception) {
            ErrorHelper::reportError($exception);

            return '';
        }

        return $twigEnvironment->render(
            '@Modules/ps_mbo/views/templates/hook/twig/module_manager_additional_description.html.twig', [
                'moduleUrl' => $moduleUrl,
                'moduleName' => $moduleName,
            ]
        );
    }
}
