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

namespace PrestaShop\Module\Mbo\Controller\Admin;

use Exception;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Module\Exception\ModuleUpgradeNotNeededException;
use PrestaShop\PrestaShop\Core\Module\ModuleManager;
use PrestaShop\PrestaShop\Core\Module\ModuleRepository;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class AddonsController extends FrameworkBundleAdminController
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    public function __construct(
        RequestStack $requestStack,
        ModuleManager $moduleManager,
        ModuleRepository $moduleRepository
    ) {
        parent::__construct();
        $this->requestStack = $requestStack;
        $this->moduleManager = $moduleManager;
        $this->moduleRepository = $moduleRepository;
    }

    public function upgradeModuleAction(): JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        $moduleName = $request->request->get('moduleName');

        if (null === $moduleName) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        try {
            $upgradeResponse = [
                'status' => $this->moduleManager->upgrade($moduleName),
                'msg' => '',
                'module_name' => $moduleName,
            ];

            if ($upgradeResponse['status'] === true) {
                $upgradeResponse['msg'] = $this->trans(
                    'Module %module% successfully upgraded.',
                    'Modules.Mbo.Modulescatalog',
                    ['%module%' => $moduleName]
                );
                $upgradeResponse['is_configurable'] = (bool) $this->moduleRepository
                    ->getModule($moduleName)
                    ->attributes
                    ->get('is_configurable');
            } else {
                $error = $this->moduleManager->getError($moduleName);
                $upgradeResponse['msg'] = $this->trans(
                    'Upgrade of module %module% failed. %error%',
                    'Modules.Mbo.Modulescatalog',
                    [
                        '%module%' => $moduleName,
                        '%error%' => $error,
                    ]
                );
            }
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
            if ($e->getPrevious() instanceof ModuleUpgradeNotNeededException) {
                $upgradeResponse['status'] = true;
                $upgradeResponse['msg'] = $this->trans(
                    'Module %module% is already up to date',
                    'Modules.Mbo.Modulescatalog',
                    [
                        '%module%' => $moduleName,
                    ]
                );
            } else {
                try {
                    $this->moduleManager->disable($moduleName);
                } catch (Exception $subE) {
                    ErrorHelper::reportError($subE);
                }

                $upgradeResponse['msg'] = $this->trans(
                    'Upgrade of module %module% failed. %error%',
                    'Modules.Mbo.Modulescatalog',
                    [
                        '%module%' => $moduleName,
                        '%error%' => $e->getMessage(),
                    ]
                );
            }
        }

        return new JsonResponse($upgradeResponse);
    }
}
