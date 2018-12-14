<?php
/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 **/

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Addon\AddonListFilter;
use PrestaShop\PrestaShop\Core\Addon\AddonListFilterStatus;
use PrestaShop\PrestaShop\Core\Addon\AddonListFilterType;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

require_once(dirname(__FILE__) . '../../../classes/Addons.php');

class AdminPsMboModuleController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->controller_quick_name = 'module';
        if ($this->ajax) {
            $this->display_header = false;
            $this->display_header_javascript = false;
            $this->display_footer = false;
        }
    }

    /**
     * Initialize the content by adding Boostrap and loading the TPL
     *
     * @param none
     * @return none
     */
    public function initContent()
    {
        parent::initContent();

        if (Tools::getIsset('filterCategoryTab')) {
            $this->context->smarty->assign(array(
                'filterCategoryTab' => Tools::getValue('filterCategoryTab')
            ));
        }

        $admin_webpath = str_ireplace(_PS_CORE_DIR_, '', _PS_ADMIN_DIR_);
        $admin_webpath = preg_replace('/^'.preg_quote(DIRECTORY_SEPARATOR, '/').'/', '', $admin_webpath);

        $container = SymfonyContainer::getInstance();
        $install_url = $container->get('router')->generate('admin_module_import', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $notification_count_url = $container->get('router')->generate('admin_module_notification_count', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $parts = parse_url($install_url);
        $moduleControllerToken = null;
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            $moduleControllerToken = $query['_token'];
        }

        $this->context->smarty->assign(array(
            'shop_name' => Configuration::get('PS_SHOP_NAME'),
            'img_dir' => _PS_IMG_,
            'iso' => $this->context->language->iso_code,
            'bootstrap'         =>  1,
            'configure_type'    => $this->controller_quick_name,
            'template_dir' => $this->module->template_dir,
            'admin_module_controller_psmbo'  => $this->module->controller_name[0],
            'admin_module_ajax_url_psmbo'    => $this->module->getControllerLink('AdminPsMboModule'),
            'currency_symbol' => Context::getContext()->currency->sign,
            'bo_img' => __PS_BASE_URI__ . $admin_webpath . '/themes/default/img/',
            'install_url' => $install_url,
            'module_controller_token' => $moduleControllerToken,
            'notification_count_url' => $notification_count_url,
            'javascript_urls' => json_encode(
                [
                    'configure' => $container->get('router')->generate(
                        'admin_module_configure_action',
                        ['module_name' => '%module_name%']
                    )
                ]
            ),
        ));

        // @TODO, call vue.min.js
        $aJs = array(
            $this->module->js_path . 'vue.js',
            $this->module->js_path . 'controllers/module/dropzone.min.js',
            $this->module->js_path . 'controllers/module/jquery.pstagger.js',
            $this->module->js_path . 'controllers/module/module.js',
            $this->module->js_path . 'controllers/module/search.js',
            $this->module->js_path . 'controllers/module/module_card.js'
        );

        $aJsDef = array(
            'admin_module_controller_psmbo'  => $this->module->controller_name[0],
            'admin_module_ajax_url_psmbo'    => $this->module->getControllerLink('AdminPsMboModule')
        );

        $aCss = array(
            _PS_ADMIN_DIR_ . '/themes/new-theme/public/theme.css',
            $this->module->css_path . 'controllers/module/module.css'
        );

        $this->module->setMedia($aJsDef, $aJs, $aCss);
        $this->setTemplate($this->module->template_dir . 'page.tpl');
    }

    protected function addonsList($request)
    {
        $results = Addons::addonsRequest($request, array('format' => 'json'));
        $results = json_decode($results, true);

        if (!isset($results['modules'])) {
            return json_encode(array());
        }

        foreach ($results['modules'] as &$result) {
            $result['origin'] = $request;
        }

        return json_encode($results);
    }

    public function displayAjaxGetModulesList()
    {
        $filters = new AddonListFilter();
        $filters->setType(AddonListFilterType::MODULE | AddonListFilterType::SERVICE)
            ->setStatus(~AddonListFilterStatus::INSTALLED);

        $container = SymfonyContainer::getInstance();
        $container->set('translator', Context::getContext()->getTranslator());

        $modulesProvider = $container->get('prestashop.core.admin.data_provider.module_interface');

        $moduleRepository = $container->get('prestashop.core.admin.module.repository');

        $filteredList = $moduleRepository->getFilteredList($filters);
        if (is_array($filteredList)) {
            $filteredList = PrestaShop\PrestaShop\Core\Addon\AddonsCollection::createFrom($filteredList);
        }

        $modules = $modulesProvider->generateAddonsUrls($filteredList);

        $categories = $container->get('prestashop.categories_provider')->getCategoriesMenu($modules);

        foreach ($categories['categories']->subMenu as &$category) {
            $category->name = html_entity_decode($this->trans($category->name, array(), 'Admin.Modules.Feature'));
        }

        // In newest versions of PrestaShop, a AddonsCollection can be returned.
        // We check that we deal with an array, as the class may not exist.
        if (!is_array($modules)) {
            $modules = $modules->toArray();
        }
        shuffle($modules);

        $modules = $this->getPresentedProducts($modules);
        foreach ($modules as $key => &$module) {
            $module['attributes']['displayName'] = html_entity_decode($module['attributes']['displayName']);
            $module['attributes']['description'] = html_entity_decode($module['attributes']['description']);
            $module['attributes']['description'] = htmlspecialchars_decode(
                $module['attributes']['description'],
                ENT_QUOTES
            );

            $module['attributes']['visible'] = true;
            $module['attributes']['price'] = isset($module['attributes']['price'][Context::getContext()->currency->iso_code]) ?
                                           $module['attributes']['price'][Context::getContext()->currency->iso_code] :
                                           '';

            // only one badge to display
            $i = 0;
            foreach ($module['attributes']['badges'] as $keyBadge => $badge) {
                if ($i > 0) {
                    unset($modules[$key]['attributes']['badges'][$keyBadge]);
                    break;
                }
                $i++;
            }
        }

        $this->ajaxRender(
            json_encode(
                [
                    'modules' => $modules,
                    'categories' => $categories['categories']
                ]
            )
        );
    }

    private function getPresentedProducts(array &$modules)
    {
        $container = SymfonyContainer::getInstance();
        $modulePresenter = $container->get('prestashop.adapter.presenter.module');
        $presentedProducts = array();
        foreach ($modules as $name => $product) {
            $presentedProducts[$name] = $modulePresenter->present($product);
        }

        return $presentedProducts;
    }

    public function displayAjaxGetMboModuleQuickView()
    {
        $modules = Module::getModulesOnDisk();

        foreach ($modules as $module) {
            if ($module->name == Tools::getValue('module_name')) {
                break;
            }
        }

        $url = $module->url;

        if (isset($module->type) && ($module->type == 'addonsPartner' || $module->type == 'addonsNative')) {
            $url = $this->context->link->getAdminLink('AdminModules').'&install='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name);
        }

        $this->context->smarty->assign(array(
            'displayName' => $module->displayName,
            'image' => $module->image,
            'nb_rates' => (int)$module->nb_rates[0],
            'avg_rate' => (int)$module->avg_rate[0],
            'badges' => $module->badges,
            'compatibility' => $module->compatibility,
            'description_full' => $module->description_full,
            'additional_description' => $module->additional_description,
            'is_addons_partner' => (isset($module->type) && ($module->type == 'addonsPartner' || $module->type == 'addonsNative')),
            'url' => $url,
            'price' => (isset($module->price)) ? $module->price : 0,
            'id_currency' => Context::getContext()->currency->id
        ));

        $this->ajaxRender($this->context->smarty->fetch($this->module->template_dir . '/quickview.tpl'));
    }

    public function displayAjaxGetTabModulesList()
    {
        $result = $this->module->fetchModulesByController(true);

        $this->context->smarty->assign(array(
            'controller_name' => $result['controller_name'],
            'currentIndex' => self::$currentIndex,
            'modules_list' => $result['modules_list'],
            'admin_module_favorites_view' => $this->context->link->getAdminLink('AdminModules').'&select=favorites',
            'lang_iso' => $this->context->language->iso_code,
            'host_mode' => defined('_PS_HOST_MODE_') ? 1 : 0,
            'from' => 'tab'
        ));
        $this->smartyOutputContent($this->module->template_dir . '/include/admin-end-content-footer-legacy.tpl');
    }

    public function displayAjaxFetchModules()
    {
        $this->ajaxRender($this->module->fetchModules(Tools::getValue('controller_page')));
    }

    public function getModulesByInstallation($tab_modules_list = null, $install_source_tracking = false)
    {
        $all_modules = Module::getModulesOnDisk(true, $this->logged_on_addons, $this->context->employee->id);
        $all_unik_modules = array();
        $modules_list = array('installed' =>array(), 'not_installed' => array());

        foreach ($all_modules as $mod) {
            if (!isset($all_unik_modules[$mod->name])) {
                $all_unik_modules[$mod->name] = $mod;
            }
        }

        $all_modules = $all_unik_modules;

        foreach ($all_modules as $module) {
            if (!isset($tab_modules_list) || in_array($module->name, $tab_modules_list)) {
                $perm = true;
                if ($module->id) {
                    $perm &= Module::getPermissionStatic($module->id, 'configure');
                } else {
                    $id_admin_module = Tab::getIdFromClassName('AdminModules');
                    $access = Profile::getProfileAccess($this->context->employee->id_profile, $id_admin_module);
                    if (!$access['edit']) {
                        $perm &= false;
                    }
                }

                if (in_array($module->name, $this->list_partners_modules)) {
                    $module->type = 'addonsPartner';
                }

                if ($perm) {
                    $this->fillModuleData($module, 'array', null, $install_source_tracking);
                    if ($module->id) {
                        $modules_list['installed'][] = $module;
                    } else {
                        $modules_list['not_installed'][] = $module;
                    }
                }
            }
        }

        return $modules_list;
    }

    public function displayAjaxGetMboAddonsThemes()
    {
        $parent_domain = Tools::getHttpHost(true) . substr($_SERVER['REQUEST_URI'], 0, -1 * strlen(basename($_SERVER['REQUEST_URI'])));
        $iso_lang = Context::getContext()->language->iso_code;
        $iso_currency = Context::getContext()->currency->iso_code;
        $iso_country = Context::getContext()->country->iso_code;
        $activity = Configuration::get('PS_SHOP_ACTIVITY');
        $addons_url = Tools::getCurrentUrlProtocolPrefix() . 'addons.prestashop.com/iframe/search-1.7.php?psVersion=' . _PS_VERSION_ . '&onlyThemes=1&isoLang=' . pSQL($iso_lang) . '&isoCurrency=' . pSQL($iso_currency) . '&isoCountry=' . pSQL($iso_country) . '&activity=' . (int) $activity . '&parentUrl=' . pSQL($parent_domain);

        $this->ajaxRender(Tools::file_get_contents($addons_url));
    }
}
