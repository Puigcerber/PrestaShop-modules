<?php

if (!defined('_PS_VERSION_'))
  exit;

class HolidayAlert extends Module
{
    public function __construct()
    {
        $this->name = 'holidayalert';
        $this->tab = 'front_office_features';
        $this->version = '1.0';
        $this->author = 'Pablo Villoslada Puigcerber';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');

        parent::__construct();

        $this->displayName = $this->l('Holiday alert');
        $this->description = $this->l('Set a message to alert your clients that the shop is on holiday.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        
        if (!Configuration::get('PVP_ALERT_CLASS'))  
            $this->warning = $this->l('No alert class provided');
    }
  
    public function install()
    {
        if (Shop::isFeatureActive())
          Shop::setContext(Shop::CONTEXT_ALL);

        return parent::install() &&
                $this->registerHook('displayTop') &&
                $this->registerHook('header') &&
                Configuration::updateValue('PVP_ALERT_CLASS', 'info') &&
                Configuration::updateValue('PVP_ALERT_MESSAGE', 'This is a sample message.');
    }
     
    public function uninstall()
    {
        return parent::uninstall() && 
                Configuration::deleteByName('PVP_ALERT_CLASS') &&
                Configuration::deleteByName('PVP_ALERT_MESSAGE');
    }
    
    public function hookDisplayTop($params)
    {
        $this->context->smarty->assign(
                array(
                    'pvp_alert_class' => Configuration::get('PVP_ALERT_CLASS'),
                    'pvp_alert_message' => Configuration::get('PVP_ALERT_MESSAGE'),
                    
                )
        );
        return $this->display(__FILE__, 'holidayalert.tpl');
    }
    
    public function hookDisplayShoppingCart($params)
    {
        return $this->hookDisplayTop($params);
    }
    
    public function hookDisplayHeader()
    {
      $this->context->controller->addCSS($this->_path.'css/holidayalert.css', 'all');
    }
    
    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name))
        {
            $pvp_alert_class = strval(Tools::getValue('PVP_ALERT_CLASS'));
            $pvp_alert_message = strval(Tools::getValue('PVP_ALERT_MESSAGE'));
            if (!$pvp_alert_class  || empty($pvp_alert_class) || !Validate::isGenericName($pvp_alert_class)) {
                $output .= $this->displayError( $this->l('Invalid alert class.') );
            }
            elseif(!$pvp_alert_message  || empty($pvp_alert_message) || !Validate::isMessage($pvp_alert_message))  {
                $output .= $this->displayError( $this->l('Invalid alert message.') );
            }                
            else
            {
                Configuration::updateValue('PVP_ALERT_CLASS', $pvp_alert_class);
                Configuration::updateValue('PVP_ALERT_MESSAGE', $pvp_alert_message);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm();
    }
    
    public function displayForm()
    {
        // Get default Language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Alert class'),
                    'name' => 'PVP_ALERT_CLASS',
                    'options' => array(                                  
                        'query' => array(
                            array(
                                'id_option' => 'danger',
                                'name' => 'danger'
                            ),
                            array(
                                'id_option' => 'info',
                                'name' => 'info'
                            ),
                            array(
                                'id_option' => 'success',
                                'name' => 'success'
                            ),
                            array(
                                'id_option' => 'warning',
                                'name' => 'warning'
                            ),
                        ),
                        'id' => 'id_option',
                        'name' => 'name'
                    ),
                    'required' => true
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Alert message'),
                    'name' => 'PVP_ALERT_MESSAGE',
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value['PVP_ALERT_CLASS'] = Configuration::get('PVP_ALERT_CLASS');
        $helper->fields_value['PVP_ALERT_MESSAGE'] = Configuration::get('PVP_ALERT_MESSAGE');

        return $helper->generateForm($fields_form);
    }
    
}
?>