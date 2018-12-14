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

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShopBundle\Service\DataProvider\Admin\AddonsInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ps_mbo extends Module
{
    public $mytabs = array(
        array(
            'name' => array(
                'en' => 'Selection'
            ),
            'visible' => true,
            'class_name' => 'AdminPsMboModule',
            'parent_class_name' => 'AdminParentModulesCatalog',
            'core_reference' => 'AdminModulesCatalog',
        ),
        array(
            'name' => array(
                'en' => 'Theme catalog'
            ),
            'visible' => true,
            'class_name' => 'AdminPsMboTheme',
            'parent_class_name' => 'AdminParentThemes',
            'core_reference' => 'AdminThemesCatalog',
        )
    );

    /**
     * Links to the admin controllers created by the module
     */
    protected $front_controller = null;

    public function __construct()
    {
        $this->name = 'ps_mbo';
        $this->version = '1.1.1';
        $this->author = 'PrestaShop';
        $this->module_key = '6cad5414354fbef755c7df4ef1ab74eb';
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('PrestaShop Marketplace in your Back Office');
        $this->description = $this->l('Discover the best PrestaShop modules to optimize your online store.');

        $this->controller_name = array('AdminPsMboModule', 'AdminPsMboTheme');

        $this->template_dir = '../../../../modules/' . $this->name . '/views/templates/admin/';

        $this->css_path = $this->_path . 'views/css/';
        $this->js_path = $this->_path . 'views/js/';
    }

    /**
     * install()
     *
     * @param none
     * @return bool
     */
    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('backOfficeHeader')
            || !$this->registerHook('displayDashboardToolbarTopMenu')
            || !$this->registerHook('displayAdminNavBarBeforeEnd')
            || !$this->registerHook('displayDashboardToolbarIcons')
            || !$this->registerHook('displayAdminEndContent')
            || !$this->installTabs()
        ) {
            $this->_errors[] = $this->l('There was an error during the installation.');
            return false;
        }

        return true;
    }

    protected function installTabs()
    {
        foreach ($this->mytabs as $data) {
            $names = array();
            foreach (Language::getLanguages(false) as $lang) {
                $names[(int) $lang['id_lang']] = reset($data['name']);
            }
            $position = 0;

            $oldTabId = Tab::getIdFromClassName($data['core_reference']);
            if ($oldTabId !== false) {
                $catalogTab = new Tab($oldTabId);
                $names = $catalogTab->name;
                $position = $catalogTab->position;
                $catalogTab->active = false;
                $catalogTab->save();
            }

            $tab = new Tab();
            $tab->module = $this->name;
            $tab->class_name = $data['class_name'];
            $tab->position = (int) $position;
            $tab->visible = true;
            $tab->id_parent = Tab::getIdFromClassName($data['parent_class_name']);
            $tab->name = $names;
            $tab->save();

            // Second save only for position
            $tab->position = (int) $position;
            $tab->save();
        }

        return true;
    }

    public function fetchModules($controller)
    {
        $controllerWhiteList = array('AdminCarriers', 'AdminPayment');
        if (!in_array($controller, $controllerWhiteList)) {
            return false;
        }

        $data = array();
        switch ($controller) {
            case 'AdminCarriers':
                $modules = $this->getCarriersMboModules();
                $data['panel_id'] = 'recommended-carriers-panel';
                $data['panel_title'] = $this->trans(
                    'Use one of our recommended carrier modules',
                    [],
                    'Admin.Shipping.Feature'
                );
                break;
            case 'AdminPayment':
                $modules = $this->getPaymentMboModules();
                break;
        }

        if (empty($modules)) {
            return false;
        }

        if ((int) Tools::getValue('legacy') == 0) {
            foreach ($modules as &$module) {
                if (isset($module->optionsHtml) && count($module->optionsHtml) > 0) {
                    $module->optionsHtml[0] = str_replace('btn btn-success', 'btn btn-primary-reverse btn-outline-primary light-button', $module->optionsHtml[0]);
                }
            }
        }

        $data['controller_name'] = $controller;
        $data['admin_module_ajax_url_psmbo'] = $this->getControllerLink('AdminPsMboModule');
        $data['from'] = 'footer';
        $data['modules_list'] = $modules;

        $this->context->smarty->assign($data);

        if ((int) Tools::getValue('legacy') == 1) {
            return $this->context->smarty->fetch($this->template_dir . '/include/admin-end-content-footer-legacy.tpl');
        }

        return $this->context->smarty->fetch($this->template_dir . '/include/admin-end-content-footer.tpl');
    }

    public function fetchModulesByController($ajax = false)
    {
        $controller = Tools::getValue('controllerName');

        // if controller is false, we try to get the other method
        if ($controller === false) {
            $controller = Tools::getValue('controller');
        }

        $controllerWhiteList = [
            'AdminCarriers',
            'AdminPayment'
        ];

        if (empty($controller) || ($ajax === false && !in_array($controller, $controllerWhiteList))) {
            return false;
        }

        $panel_id = '';
        $modules = [];
        switch ($controller) {
            case 'AdminCarriers':
                $modules = $this->getCarriersMboModules();
                $panel_id = 'recommended-carriers-panel';
                $this->context->smarty->assign(
                    'panel_title',
                    $this->trans('Use one of our recommended carrier modules', [], 'Admin.Shipping.Feature')
                );

                break;
            case 'AdminPayment':
                $modules = $this->getPaymentMboModules();
                break;
            default:
                $filter_modules_list = $this->getFilterList($controller);
                $tracking_source = 'back-office, ' . $controller;
                $modules = $this->getModules($filter_modules_list, $tracking_source);
                break;
        }

        if (empty($modules)) {
            return false;
        }

        $data = array(
            'panel_id' => $panel_id,
            'controller_name' => $controller,
            'admin_module_ajax_url_psmbo' => $this->getControllerLink('AdminPsMboModule'),
            'from' => 'footer',
            'modules_list' => $modules,
        );

        $this->context->smarty->assign($data);

        if ($ajax === true) {
            return $data;
        }

        return $this->context->smarty->fetch($this->template_dir . '/include/admin-end-content-footer.tpl');
    }

    /**
     * @param string $tab The controller we want a link for.
     * 
     * @return string Admin link with token
     */
    public function getControllerLink($tab)
    {
        if (null === $this->front_controller) {
            $this->front_controller =  array();
            foreach ($this->controller_name as $name) {
                $this->front_controller[$name] = 'index.php?controller=' . $name . '&token=' . Tools::getAdminTokenLite($name);
            }
        }

        if (isset($this->front_controller[$tab])) {
            return $this->front_controller[$tab];
        }
        throw new Exception('Unknown controller requested.');
    }

    protected function handleAddonsConnectWithMbo()
    {
        if (Tools::getValue('controller') !== 'AdminPsMboModule') {
            return false;
        }

        $addonsConnect = $this->getAddonsConnectToolbar();

        $this->context->smarty->assign(array(
            'addons_connect' => $addonsConnect,
        ));

        return $this->context->smarty->fetch($this->template_dir . '/include/modal_addons_connect.tpl');
    }

    protected function handleTheme()
    {
        if (Tools::getValue('controller') !== 'AdminThemes') {
            return false;
        }

        $this->context->smarty->assign([
            'admin_module_ajax_url_psmbo' => $this->getControllerLink('AdminPsMboModule')
        ]);
        return $this->context->smarty->fetch($this->template_dir . '/admin-end-content-theme.tpl');
    }

    public function hookDisplayAdminEndContent()
    {
        $connectWithMbo = $this->handleAddonsConnectWithMbo();
        if ($connectWithMbo !== false) {
            return $connectWithMbo;
        }

        $handleTheme = $this->handleTheme();
        if ($handleTheme !== false) {
            return $handleTheme;
        }

        $content = '';
        $content .= $this->context->smarty->fetch($this->template_dir . '/modal.tpl');

        $controller_page = (Tools::getIsset('controller')) ? Tools::getValue('controller') : '';
        $controllerWhiteList = array('AdminCarriers', 'AdminPayment');
        if (in_array($controller_page, $controllerWhiteList)) {
            $this->context->smarty->assign(array(
                'admin_module_ajax_url_psmbo' => $this->getControllerLink('AdminPsMboModule'),
                'controller_page' => $controller_page
            ));

            if (ADMIN_LEGACY_CONTEXT === true) {
                $content .= $this->context->smarty->fetch($this->template_dir . '/admin-end-content-legacy.tpl');
            } else {
                $content .= $this->context->smarty->fetch($this->template_dir . '/admin-end-content.tpl');
            }
        }

        return $content;
    }

    public function hookDisplayDashboardToolbarTopMenu()
    {
        if (Tools::getValue('controller') === 'AdminPsMboModule') {
            $addonsConnect = $this->getAddonsConnectToolbar();

            $this->context->smarty->assign(array(
                'addons_connect' => $addonsConnect,
            ));

            return $this->context->smarty->fetch($this->template_dir . '/module-toolbar.tpl');
        }

        $data = array();
        $data['controller'] = Tools::getValue('controller');
        $data['admin_module_ajax_url_psmbo'] = $this->context->link->getAdminLink('AdminPsMboModule');
        $data['isSymfonyContext'] = $this->isSymfonyContext();
        $this->context->smarty->assign($data);

        return $this->context->smarty->fetch($this->template_dir . '/toolbar.tpl');
    }

    private function getAddonsConnectToolbar()
    {
        $container = SymfonyContainer::getInstance();
        $addonsProvider = $container->get('prestashop.core.admin.data_provider.addons_interface');
        $addonsConnect = [];

        $authenticated = $addonsProvider->isAddonsAuthenticated();

        if ($addonsProvider->isAddonsAuthenticated()) {
            $addonsEmail = $addonsProvider->getAddonsEmail();
            return array(
                'connected' => true,
                'email' => $addonsEmail['username_addons'],
                'logout_url' => $container->get('router')->generate(
                    'admin_addons_logout',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            );
        }

        return array(
            'connected' => false,
            'login_url' => $container->get('router')->generate(
                'admin_addons_login',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );
    }

    /**
     * uninstall()
     *
     * @param none
     * @return bool
     */
    public function uninstall()
    {
        // unregister hook
        if (!parent::uninstall()) {
            $this->_errors[] = $this->l('There was an error during the desinstallation.');
            return false;
        }

        $idTab = Tab::getIdFromClassName('AdminModulesCatalog');
        if ($idTab !== false) {
            $catalogTab = new Tab($idTab);
            $catalogTab->active = true;
            $catalogTab->save();
        }

        $idTab = Tab::getIdFromClassName('AdminThemesCatalog');
        if ($idTab !== false) {
            $catalogTab = new Tab($idTab);
            $catalogTab->active = true;
            $catalogTab->save();
        }

        $idTab = Tab::getIdFromClassName('AdminPsMboTheme');
        if ($idTab !== false) {
            $catalogTab = new Tab($idTab);
            $catalogTab->delete();
        }

        $idTab = Tab::getIdFromClassName('AdminPsMboModule');
        if ($idTab !== false) {
            $catalogTab = new Tab($idTab);
            $catalogTab->delete();
        }

        return true;
    }

    /**
     * set JS and CSS media
     *
     * @param none
     * @return none
     */
    public function setMedia($aJsDef, $aJs, $aCss)
    {
        Media::addJsDef($aJsDef);
        $this->context->controller->addCSS($aCss);
        $this->context->controller->addJS($aJs);
    }

    public function getCarriersMboModules()
    {
        $filter_modules_list = $this->getFilterList('AdminCarriers');
        $tracking_source = 'back-office,AdminCarriers,new';
        return $this->getModules($filter_modules_list, $tracking_source);
    }

    public function getFilterList($className)
    {
        $idTab = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
            SELECT t.id_tab
            FROM ' . _DB_PREFIX_ . 'tab t
            WHERE t.class_name LIKE "' . pSQL($className) . '"');

        $tab_modules_list = Tab::getTabModulesList($idTab);
        $filter_modules_list = [];

        if (is_array($tab_modules_list['default_list']) && count($tab_modules_list['default_list'])) {
            $filter_modules_list = $tab_modules_list['default_list'];
        } elseif (is_array($tab_modules_list['slider_list']) && count($tab_modules_list['slider_list'])) {
            $filter_modules_list = $tab_modules_list['slider_list'];
        }

        return $filter_modules_list;
    }

    public function getModules($filter_modules_list, $tracking_source)
    {
        $all_modules = Module::getModulesOnDisk(true);
        $modules_list = [];

        foreach ($all_modules as $module) {
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

            if (in_array($module->name, $filter_modules_list) && $perm) {
                $this->fillModuleData($module, 'array', null, 'back-office,AdminCarriers,new');
                $modules_list[array_search($module->name, $filter_modules_list)] = $module;
            }
        }

        ksort($modules_list);
        return $modules_list;
    }

    protected function getPaymentMboModules()
    {
        // fillModuleData back-office,AdminPayment,index
        $filter_modules_list = $this->getFilterList('AdminPayment');

        $tracking_source = 'back-office,AdminPayment,index';
        $modulesList = $this->getModules($filter_modules_list, $tracking_source);

        $modules = [];
        foreach ($modulesList as $key => $module) {
            if (isset($module->description_full) && trim($module->description_full) != '') {
                $module->show_quick_view = true;
            }

            // Remove all options but 'configure' and install
            // All other operation should take place in new Module page
            if (($module->installed && $module->active) || !$module->installed) {
                // Unfortunately installed but disabled module will have $module->installed = false
                if (strstr($module->optionsHtml[0], 'enable=1')) {
                    $module->optionsHtml = [];
                } else {
                    $module->optionsHtml = array($module->optionsHtml[0]);
                }
            } else {
                $module->optionsHtml = [];
            }

            if (!$module->active) {
                $modules[] = $module;
            }
        }

        return $modules;
    }

    public function fillModuleData(&$module, $output_type = 'link', $back = null, $install_source_tracking = false)
    {
        /** @var Module $obj */
        $obj = null;
        if ($module->onclick_option) {
            $obj = new $module->name();
        }
        // Fill module data
        $module->logo = '../../img/questionmark.png';

        if (@filemtime(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.basename(_PS_MODULE_DIR_).DIRECTORY_SEPARATOR.$module->name
                       .DIRECTORY_SEPARATOR.'logo.gif')) {
            $module->logo = 'logo.gif';
        }
        if (@filemtime(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.basename(_PS_MODULE_DIR_).DIRECTORY_SEPARATOR.$module->name
                       .DIRECTORY_SEPARATOR.'logo.png')) {
            $module->logo = 'logo.png';
        }

        $link_admin_modules = $this->context->link->getAdminLink('AdminModules', true);

        $module->options['install_url'] = $this->context->link->getAdminLink('AdminModules') . '&install=' . $module->name . '&module_name=' . $module->name . '&tab_module=' . $module->tab;
        $module->options['update_url'] = $this->context->link->getAdminLink('AdminModules') . '&checkAndUpdate=1&module_name=' . $module->name;
        $module->options['uninstall_url'] = $this->context->link->getAdminLink('AdminModules') . '&module_name=' . $module->name . '&uninstall=' . $module->name . '&tab_module=' . $module->tab;

        // free modules get their source tracking data here
        $module->optionsHtml = $this->displayModuleOptions($module, $output_type, $back, $install_source_tracking);
        // pay modules get their source tracking data here
        if ($install_source_tracking && isset($module->addons_buy_url)) {
            $module->addons_buy_url .= ($install_source_tracking ? '&utm_term='.$install_source_tracking : '');
        }

        $module->options['uninstall_onclick'] = ((!$module->onclick_option) ?
                                                 ((empty($module->confirmUninstall)) ? 'return confirm(\''.$this->l('Do you really want to uninstall this module?').'\');' : 'return confirm(\''.addslashes($module->confirmUninstall).'\');') :
                                                 $obj->onclickOption('uninstall', $module->options['uninstall_url']));

        if ((Tools::getValue('module_name') == $module->name || in_array($module->name, explode('|', Tools::getValue('modules_list')))) && (int)Tools::getValue('conf') > 0) {
            $module->message = $this->_conf[(int)Tools::getValue('conf')];
        }

        if ((Tools::getValue('module_name') == $module->name || in_array($module->name, explode('|', Tools::getValue('modules_list')))) && (int)Tools::getValue('conf') > 0) {
            unset($obj);
        }
    }

    /**
     * Display modules list
     *
     * @param Module $module
     * @param string $output_type (link or select)
     * @param string|null $back
     * @param string|bool $install_source_tracking
     * @return string|array
     */
    public function displayModuleOptions($module, $output_type = 'link', $back = null, $install_source_tracking = false)
    {
        if (!isset($module->enable_device)) {
            $module->enable_device = Context::DEVICE_COMPUTER | Context::DEVICE_TABLET | Context::DEVICE_MOBILE;
        }

        $this->translationsTab['confirm_uninstall_popup'] = (isset($module->confirmUninstall) ? $module->confirmUninstall : $this->l('Do you really want to uninstall this module?'));
        if (!isset($this->translationsTab['Disable this module'])) {
            $this->translationsTab['Disable this module'] = $this->l('Disable this module');
            $this->translationsTab['Enable this module for all shops'] = $this->l('Enable this module for all shops');
            $this->translationsTab['Disable'] = $this->l('Disable');
            $this->translationsTab['Enable'] = $this->l('Enable');
            $this->translationsTab['Disable on mobiles'] = $this->l('Disable on mobiles');
            $this->translationsTab['Disable on tablets'] = $this->l('Disable on tablets');
            $this->translationsTab['Disable on computers'] = $this->l('Disable on computers');
            $this->translationsTab['Display on mobiles'] = $this->l('Display on mobiles');
            $this->translationsTab['Display on tablets'] = $this->l('Display on tablets');
            $this->translationsTab['Display on computers'] = $this->l('Display on computers');
            $this->translationsTab['Reset'] = $this->l('Reset');
            $this->translationsTab['Configure'] = $this->l('Configure');
            $this->translationsTab['Delete'] = $this->l('Delete');
            $this->translationsTab['Install'] = $this->l('Install');
            $this->translationsTab['Uninstall'] = $this->l('Uninstall');
            $this->translationsTab['Would you like to delete the content related to this module ?'] = $this->l('Would you like to delete the content related to this module ?');
            $this->translationsTab['This action will permanently remove the module from the server. Are you sure you want to do this?'] = $this->l('This action will permanently remove the module from the server. Are you sure you want to do this?');
            $this->translationsTab['Remove from Favorites'] = $this->l('Remove from Favorites');
            $this->translationsTab['Mark as Favorite'] = $this->l('Mark as Favorite');
        }

        $link_admin_modules = $this->context->link->getAdminLink('AdminModules', true);
        $modules_options = [];

        $configure_module = array(
            'href' => $link_admin_modules.'&configure='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.urlencode($module->name),
            'onclick' => $module->onclick_option && isset($module->onclick_option_content['configure']) ? $module->onclick_option_content['configure'] : '',
            'title' => '',
            'text' => $this->translationsTab['Configure'],
            'cond' => $module->id && isset($module->is_configurable) && $module->is_configurable,
            'icon' => 'wrench',
        );

        $desactive_module = array(
            'href' => $link_admin_modules.'&module_name='.urlencode($module->name).'&'.($module->active ? 'enable=0' : 'enable=1').'&tab_module='.$module->tab,
            'onclick' => $module->active && $module->onclick_option && isset($module->onclick_option_content['desactive']) ? $module->onclick_option_content['desactive'] : '' ,
            'title' => Shop::isFeatureActive() ? htmlspecialchars($module->active ? $this->translationsTab['Disable this module'] : $this->translationsTab['Enable this module for all shops']) : '',
            'text' => $module->active ? $this->translationsTab['Disable'] : $this->translationsTab['Enable'],
            'cond' => $module->id,
            'icon' => 'off',
        );
        $link_reset_module = $link_admin_modules.'&module_name='.urlencode($module->name).'&reset&tab_module='.$module->tab;

        $is_reset_ready = false;
        if (Validate::isModuleName($module->name)) {
            if (method_exists(Module::getInstanceByName($module->name), 'reset')) {
                $is_reset_ready = true;
            }
        }

        $reset_module = array(
            'href' => $link_reset_module,
            'onclick' => $module->onclick_option && isset($module->onclick_option_content['reset']) ? $module->onclick_option_content['reset'] : '',
            'title' => '',
            'text' => $this->translationsTab['Reset'],
            'cond' => $module->id && $module->active,
            'icon' => 'undo',
            'class' => ($is_reset_ready ? 'reset_ready' : '')
        );

        $delete_module = array(
            'href' => $link_admin_modules.'&delete='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.urlencode($module->name),
            'onclick' => $module->onclick_option && isset($module->onclick_option_content['delete']) ? $module->onclick_option_content['delete'] : 'return confirm(\''.$this->translationsTab['This action will permanently remove the module from the server. Are you sure you want to do this?'].'\');',
            'title' => '',
            'text' => $this->translationsTab['Delete'],
            'cond' => true,
            'icon' => 'trash',
            'class' => 'text-danger'
        );

        $display_mobile = array(
            'href' => $link_admin_modules.'&module_name='.urlencode($module->name).'&'.($module->enable_device & Context::DEVICE_MOBILE ? 'disable_device' : 'enable_device').'='.Context::DEVICE_MOBILE.'&tab_module='.$module->tab,
            'onclick' => '',
            'title' => htmlspecialchars($module->enable_device & Context::DEVICE_MOBILE ? $this->translationsTab['Disable on mobiles'] : $this->translationsTab['Display on mobiles']),
            'text' => $module->enable_device & Context::DEVICE_MOBILE ? $this->translationsTab['Disable on mobiles'] : $this->translationsTab['Display on mobiles'],
            'cond' => $module->id,
            'icon' => 'mobile'
        );

        $display_tablet = array(
            'href' => $link_admin_modules.'&module_name='.urlencode($module->name).'&'.($module->enable_device & Context::DEVICE_TABLET ? 'disable_device' : 'enable_device').'='.Context::DEVICE_TABLET.'&tab_module='.$module->tab,
            'onclick' => '',
            'title' => htmlspecialchars($module->enable_device & Context::DEVICE_TABLET ? $this->translationsTab['Disable on tablets'] : $this->translationsTab['Display on tablets']),
            'text' => $module->enable_device & Context::DEVICE_TABLET ? $this->translationsTab['Disable on tablets'] : $this->translationsTab['Display on tablets'],
            'cond' => $module->id,
            'icon' => 'tablet'
        );

        $display_computer = array(
            'href' => $link_admin_modules.'&module_name='.urlencode($module->name).'&'.($module->enable_device & Context::DEVICE_COMPUTER ? 'disable_device' : 'enable_device').'='.Context::DEVICE_COMPUTER.'&tab_module='.$module->tab,
            'onclick' => '',
            'title' => htmlspecialchars($module->enable_device & Context::DEVICE_COMPUTER ? $this->translationsTab['Disable on computers'] : $this->translationsTab['Display on computers']),
            'text' => $module->enable_device & Context::DEVICE_COMPUTER ? $this->translationsTab['Disable on computers'] : $this->translationsTab['Display on computers'],
            'cond' => $module->id,
            'icon' => 'desktop'
        );

        $install = array(
            'href' => $link_admin_modules.'&install='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name)
            .(!is_null($back) ? '&back='.urlencode($back) : '').($install_source_tracking ? '&source='.$install_source_tracking : ''),
            'onclick' => '',
            'title' => $this->translationsTab['Install'],
            'text' => $this->translationsTab['Install'],
            'cond' => $module->id,
            'icon' => 'plus-sign-alt'
        );

        $uninstall = array(
            'href' => $link_admin_modules.'&uninstall='.urlencode($module->name).'&tab_module='.$module->tab.'&module_name='.$module->name.'&anchor='.ucfirst($module->name).(!is_null($back) ? '&back='.urlencode($back) : ''),
            'onclick' => (isset($module->onclick_option_content['uninstall']) ? $module->onclick_option_content['uninstall'] : 'return confirm(\''.$this->translationsTab['confirm_uninstall_popup'].'\');'),
            'title' => $this->translationsTab['Uninstall'],
            'text' => $this->translationsTab['Uninstall'],
            'cond' => $module->id,
            'icon' => 'minus-sign-alt'
        );

        $remove_from_favorite = array(
            'href' => '#',
            'class' => 'action_unfavorite toggle_favorite',
            'onclick' =>'',
            'title' => $this->translationsTab['Remove from Favorites'],
            'text' => $this->translationsTab['Remove from Favorites'],
            'cond' => $module->id,
            'icon' => 'star',
            'data-value' => '0',
            'data-module' => $module->name
        );

        $mark_as_favorite = array(
            'href' => '#',
            'class' => 'action_favorite toggle_favorite',
            'onclick' => '',
            'title' => $this->translationsTab['Mark as Favorite'],
            'text' => $this->translationsTab['Mark as Favorite'],
            'cond' => $module->id,
            'icon' => 'star',
            'data-value' => '1',
            'data-module' => $module->name
        );

        $update = array(
            'href' => $module->options['update_url'],
            'onclick' => '',
            'title' => 'Update it!',
            'text' => 'Update it!',
            'icon' => 'refresh',
            'cond' => $module->id,
        );

        $divider = array(
            'href' => '#',
            'onclick' => '',
            'title' => 'divider',
            'text' => 'divider',
            'cond' => $module->id,
        );

        if (isset($module->version_addons) && $module->version_addons) {
            $modules_options[] = $update;
        }

        if ($module->active) {
            $modules_options[] = $configure_module;
            $modules_options[] = $desactive_module;
            $modules_options[] = $display_mobile;
            $modules_options[] = $display_tablet;
            $modules_options[] = $display_computer;
        } else {
            $modules_options[] = $desactive_module;
            $modules_options[] = $configure_module;
        }

        $modules_options[] = $reset_module;

        if ($output_type == 'select') {
            if (!$module->id) {
                $modules_options[] = $install;
            } else {
                $modules_options[] = $uninstall;
            }
        } elseif ($output_type == 'array') {
            if ($module->id) {
                $modules_options[] = $uninstall;
            }
        }

        if (isset($module->preferences) && isset($module->preferences['favorite']) && $module->preferences['favorite'] == 1) {
            $remove_from_favorite['style'] = '';
            $mark_as_favorite['style'] = 'display:none;';
            $modules_options[] = $remove_from_favorite;
            $modules_options[] = $mark_as_favorite;
        } else {
            $mark_as_favorite['style'] = '';
            $remove_from_favorite['style'] = 'display:none;';
            $modules_options[] = $remove_from_favorite;
            $modules_options[] = $mark_as_favorite;
        }

        if ($module->id == 0) {
            $install['cond'] = 1;
            $install['flag_install'] = 1;
            $modules_options[] = $install;
        }
        $modules_options[] = $divider;
        $modules_options[] = $delete_module;

        $return = '';
        foreach ($modules_options as $option_name => $option) {
            if ($option['cond']) {
                if ($output_type == 'link') {
                    $return .= '<li><a class="'.$option_name.' action_module';
                    $return .= '" href="'.$option['href'].(!is_null($back) ? '&back='.urlencode($back) : '').'"';
                    $return .= ' onclick="'.$option['onclick'].'"  title="'.$option['title'].'"><i class="icon-'.(isset($option['icon']) && $option['icon'] ? $option['icon']:'cog').'"></i>&nbsp;'.$option['text'].'</a></li>';
                } elseif ($output_type == 'array') {
                    if (!is_array($return)) {
                        $return = [];
                    }

                    $html = '<a class="';

                    $is_install = isset($option['flag_install']) ? true : false;

                    if (isset($option['class'])) {
                        $html .= $option['class'];
                    }
                    if ($is_install) {
                        $html .= ' btn btn-success';
                    }
                    if (!$is_install && count($return) == 0) {
                        $html .= ' btn btn-default';
                    }

                    $html .= '"';

                    if (isset($option['data-value'])) {
                        $html .= ' data-value="'.$option['data-value'].'"';
                    }

                    if (isset($option['data-module'])) {
                        $html .= ' data-module="'.$option['data-module'].'"';
                    }

                    if (isset($option['style'])) {
                        $html .= ' style="'.$option['style'].'"';
                    }

                    $html .= ' href="'.htmlentities($option['href']).(!is_null($back) ? '&back='.urlencode($back) : '').'" onclick="'.$option['onclick'].'"  title="'.$option['title'].'"><i class="icon-'.(isset($option['icon']) && $option['icon'] ? $option['icon']:'cog').'"></i> '.$option['text'].'</a>';
                    $return[] = $html;
                } elseif ($output_type == 'select') {
                    $return .= '<option id="'.$option_name.'" data-href="'.htmlentities($option['href']).(!is_null($back) ? '&back='.urlencode($back) : '').'" data-onclick="'.$option['onclick'].'">'.$option['text'].'</option>';
                }
            }
        }

        if ($output_type == 'select') {
            $return = '<select id="select_'.$module->name.'">'.$return.'</select>';
        }

        return $return;
    }
}
