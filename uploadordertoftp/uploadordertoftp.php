<?php

if (!defined('_PS_VERSION_'))
  exit;

class UploadOrderToFTP extends Module
{
    private $_file_name;
    private $_local_file;
    
    public function __construct()
    {
        $this->name = 'uploadordertoftp';
        $this->tab = 'billing_invoicing';
        $this->version = '1.0';
        $this->author = 'Pablo Villoslada Puigcerber';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');

        parent::__construct();

        $this->displayName = $this->l('Upload order to FTP');
        $this->description = $this->l('Save every order to a text file and upload it to a remote FTP server.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }
  
    public function install()
    {
        if (Shop::isFeatureActive())
          Shop::setContext(Shop::CONTEXT_ALL);

        return parent::install() &&
          $this->registerHook('actionValidateOrder');
    }
     
    public function uninstall()
    {
      return parent::uninstall() &&
              Configuration::deleteByName('PVP_FTP_SERVER') &&
              Configuration::deleteByName('PVP_FTP_PATH') &&
              Configuration::deleteByName('PVP_FTP_USERNAME') &&
              Configuration::deleteByName('PVP_FTP_PASSWORD') &&
              Configuration::deleteByName('PVP_FTP_PASSIVE');
    }
    
    public function hookActionValidateOrder($params)
    {
        $currency = $params['currency'];
        $order = $params['order'];
        $customer = $params['customer'];
        $delivery = new Address((int)$order->id_address_delivery);
        $invoice = new Address((int)$order->id_address_invoice);
        $products = $params['order']->getProducts();
        
        $template_products = array();
        foreach ($products as $key => $product)
	{
            $unit_price = $product['product_price_wt'];
            $product_vars = array(
                'product_reference' => $product['product_reference'],
                'product_quantity' => (int)$product['product_quantity'],
                //'unit_price' => Tools::displayPrice($unit_price, $currency, false)
                'unit_price_tax_excl' => (float)number_format($product['product_price'], 2, '.','')
            );
            $template_products[] = $product_vars;
        }
        
        $discount_pct = ((float)$order->total_discounts_tax_excl * 100) / (float)$order->getTotalProductsWithoutTaxes();
                
        $template_vars = array(
            'customer_id' => (int)$customer->id,
            'order_name' => sprintf('%06d', $order->id),
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'email' => $customer->email,
            'delivery_phone' => $delivery->phone ? $delivery->phone : $delivery->phone_mobile,
            'invoice_company' => $invoice->company,
            'invoice_dni' => $invoice->dni,
            'invoice_vat_number' => $invoice->vat_number,
            'invoice_address1' => $invoice->address1,
            'invoice_address2' => $invoice->address2,
            'invoice_city' => $invoice->city,
            'invoice_postal_code' => $invoice->postcode,
            'invoice_country' => $invoice->country,
            'delivery_address1' => $delivery->address1,
            'delivery_address2' => $delivery->address2,
            'delivery_city' => $delivery->city,
            'delivery_postal_code' => $delivery->postcode,
            'delivery_country' => $delivery->country,
            'payment' => $order->payment,
            'total_paid' => Tools::displayPrice($order->total_paid, $currency),
	    'total_products' => Tools::displayPrice($order->getTotalProductsWithTaxes(), $currency),
            'total_discounts' => Tools::displayPrice($order->total_discounts, $currency),
            'total_shipping' => Tools::displayPrice($order->total_shipping, $currency),
            'total_shipping_tax_excl' => (float)number_format($order->total_shipping_tax_excl, 2, '.', ''),
            //'total_wrapping' => Tools::displayPrice($order->total_wrapping, $currency),
            'discount_pct' => (float)number_format($discount_pct, 2, '.', ''),
            'products' => $template_products
            );
        
        $this->context->smarty->assign($template_vars);
        $output = $this->context->smarty->fetch(dirname(__FILE__).'/templates/order.tpl');
        
        $this->_file_name = str_pad($order->id, 6, '0', STR_PAD_LEFT);
        $this->writeOrderToFile($output);
        $this->uploadFileToFTP();
    }
    
    public function writeOrderToFile($content) {
        $this->_local_file = dirname(__FILE__).'/orders/'.$this->_file_name.'.txt';
        if (! file_put_contents($this->_local_file, $content)) {
           Logger::AddLog('File can not be saved to '.$this->_local_file); 
        }
    }   
        
    public function uploadFileToFTP() {        
        $ftp_server = Configuration::get('PVP_FTP_SERVER');
        $ftp_path = Configuration::get('PVP_FTP_PATH');
        $ftp_username = Configuration::get('PVP_FTP_USERNAME');
        $ftp_password = Configuration::get('PVP_FTP_PASSWORD');
        $ftp_is_passive = (bool)Configuration::get('PVP_FTP_PASSIVE');

        $remote_file = $ftp_path.'/'.$this->_file_name.'.txt';
        if (file_exists($this->_local_file)) {
            $conn_id = ftp_connect($ftp_server);
            if ($conn_id) {
                $login_result = ftp_login($conn_id, $ftp_username, $ftp_password);
                ftp_pasv($conn_id, $ftp_is_passive);
                if ($login_result) {
                   if (! ftp_put($conn_id, $remote_file, $this->_local_file, FTP_ASCII)) {
                      Logger::AddLog('There was a problem uploading '.$remote_file); 
                   }
                } else {
                    Logger::AddLog('Could not connect as '.$ftp_username); 
                }              
                ftp_close($conn_id);
            } else {
               Logger::AddLog('Could not connect to '.$ftp_server); 
            }                       
        }       
    }
    
    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name))
        {
            $pvp_ftp_server = strval(Tools::getValue('PVP_FTP_SERVER'));
            $pvp_ftp_path = strval(Tools::getValue('PVP_FTP_PATH'));
            $pvp_ftp_username = strval(Tools::getValue('PVP_FTP_USERNAME'));
            $pvp_ftp_password = strval(Tools::getValue('PVP_FTP_PASSWORD'));
            $pvp_ftp_passive = intval(Tools::getValue('PVP_FTP_PASSIVE'));
            if (!$pvp_ftp_server  || empty($pvp_ftp_server) || !Validate::isUrl($pvp_ftp_server)) {
                $output .= $this->displayError( $this->l('Invalid server address.') );
            }
            if ($pvp_ftp_path && !empty($pvp_ftp_path) && !Validate::isRoutePattern($pvp_ftp_path))  {
                $output .= $this->displayError( $this->l('Invalid path.') );
            }
            if (!$pvp_ftp_username  || empty($pvp_ftp_username) || !Validate::isGenericName($pvp_ftp_username)) {
                $output .= $this->displayError( $this->l('Invalid username.') );
            }
            if (!$pvp_ftp_password  || empty($pvp_ftp_password) || !Validate::isPasswd($pvp_ftp_password, 2)) {
                $output .= $this->displayError( $this->l('Invalid password.') );
            }
            if (!Validate::isBool($pvp_ftp_passive)) {
                $output .= $this->displayError( $this->l('Invalid passive mode.') );
            }
            if ($output === null)
            {
                Configuration::updateValue('PVP_FTP_SERVER', $pvp_ftp_server);
                Configuration::updateValue('PVP_FTP_PATH', $pvp_ftp_path);
                Configuration::updateValue('PVP_FTP_USERNAME', $pvp_ftp_username);
                Configuration::updateValue('PVP_FTP_PASSWORD', $pvp_ftp_password);// TODO: mcrypt_encrypt
                Configuration::updateValue('PVP_FTP_PASSIVE', $pvp_ftp_passive);
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
                'title' => $this->l('FTP Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Server address'),
                    'name' => 'PVP_FTP_SERVER',
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Path'),
                    'name' => 'PVP_FTP_PATH',
                    'required' => false
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Username'),
                    'name' => 'PVP_FTP_USERNAME',
                    'required' => true
                ),
                array(
                    'type' => 'password',
                    'label' => $this->l('Password'),
                    'name' => 'PVP_FTP_PASSWORD',
                    'required' => true
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Behind firewall?'),
                    'name' => 'PVP_FTP_PASSIVE',
                    'is_bool' => true,
                    'required' => true,
                    'values' => array(
                        array(
                            'id'    => 'passive_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id'    => 'passive_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    )
                ),
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
        $helper->fields_value['PVP_FTP_SERVER'] = Configuration::get('PVP_FTP_SERVER');
        $helper->fields_value['PVP_FTP_PATH'] = Configuration::get('PVP_FTP_PATH');
        $helper->fields_value['PVP_FTP_USERNAME'] = Configuration::get('PVP_FTP_USERNAME');
        $helper->fields_value['PVP_FTP_PASSWORD'] = Configuration::get('PVP_FTP_PASSWORD');
        $helper->fields_value['PVP_FTP_PASSIVE'] = Configuration::get('PVP_FTP_PASSIVE');
        
        return $helper->generateForm($fields_form);
    }
}
?>
