<?php
/**
 * 2019-2020 Team Ever
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 *  @author    Team Ever <https://www.team-ever.com/>
 *  @copyright 2019-2020 Team Ever
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_.'everpsbookstore/models/EverPsBookstoreSeller.php';

class Everpsbookstore extends PaymentModule
{
    private $html;
    private $generateRandomString;
    private $postErrors = array();
    private $postSuccess = array();

    public function __construct()
    {
        $this->name = 'everpsbookstore';
        $this->tab = 'others';
        $this->version = '2.1.3';
        $this->author = 'Team Ever';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Ever PS Bookstore');
        $this->displayPaymentName = $this->l('Paid in store');
        $this->description = $this->l('Help bookstores manage their online store');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->isSeven = Tools::version_compare(_PS_VERSION_, '1.7', '>=') ? true : false;
        $this->context->smarty->assign(array(
            'everimg_dir' => $this->_path.'views/img'
        ));
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }
        include(dirname(__FILE__).'/sql/install.php');
        Configuration::updateValue('EVERPSBOOKSTORE_CUSTOMERS_IDS', '[1]');
        Configuration::updateValue('EVERPSBOOKSTORE_ID_CARRIER', 0);
        $this->createBookstoreOrderState();
        $this->createBookstoreFeatures();
        $this->createBookstoreManufacturers();

        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('moduleRoutes')
            && $this->registerHook('actionCartSave')
            && $this->registerHook('actionObjectCustomerAddAfter')
            && $this->registerHook('actionObjectCustomerUpdateAfter')
            && $this->registerHook('actionObjectCustomerDeleteAfter')
            && $this->registerHook('actionObjectProductDeleteAfter')
            && $this->registerHook('actionObjectProductUpdateAfter')
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionOrderStatusUpdate')
            && $this->registerHook('backOfficeHeader')
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayReassurance')
            && $this->registerHook('displayAdminOrder')
            && $this->registerHook('displayOrderConfirmation')
            && $this->registerHook('displayFooterProduct')
            && $this->registerHook('displayHome')
            && $this->registerHook('displayLeftColumn')
            && $this->registerHook('displayRightColumn')
            && $this->registerHook('displayProductPriceBlock')
            && $this->registerHook('header')
            && $this->registerHook('updateCarrier');
    }

    private function installModuleTab($tabClass, $parent, $tabName)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $tabClass;
        $tab->id_parent = (int)Tab::getIdFromClassName($parent);
        $tab->position = Tab::getNewLastPosition($tab->id_parent);
        $tab->module = $this->name;
        if ($tabClass == 'AdminEverPsBookstore' && $this->isSeven) {
            $tab->icon = 'icon-team-ever';
        }

        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int)$lang['id_lang']] = $tabName;
        }

        return $tab->add();
    }

    private function createBookstoreManufacturers()
    {
        $authors = array(
            $this->l('Unknown'),
            $this->l('Victor Hugo'),
            $this->l('Umberto Eco'),
            $this->l('Philip K. Dick'),
            $this->l('Alain Damasio')
        );
        foreach ($authors as $author_name) {
            $author = new Manufacturer();
            $author->name = $author_name;
            $author->active = true;
            $author->save();
        }
    }

    private function createBookstoreFeatures()
    {
        $features = array(
            'date' => $this->l('Date'),
            'condition' => $this->l('Condition')
        );
        $featureDates = array(
            $this->l('Not set'),
            $this->l('1950'),
            $this->l('1960'),
            $this->l('1970'),
            $this->l('1980'),
            $this->l('1990'),
            $this->l('2000')
        );
        $featureConditions = array(
            $this->l('New'),
            $this->l('Refurbished'),
            $this->l('Used'),
            $this->l('Missing pages'),
            $this->l('Almost new'),
            $this->l('In blister')
        );
        foreach ($features as $type => $feat) {
            $feature = new Feature();
            foreach (Language::getLanguages(false) as $lang) {
                $feature->name[(int)$lang['id_lang']] = $feat;
            }
            if (!$feature->save()) {
                return false;
            } else {
                switch ($type) {
                    case 'date':
                        foreach ($featureDates as $value) {
                            $featureValue = new FeatureValue();
                            $featureValue->id_feature = $feature->id;
                            foreach (Language::getLanguages(false) as $lang) {
                                $featureValue->value[(int)$lang['id_lang']] = $value;
                            }
                            $featureValue->save();
                        }
                        break;

                    case 'condition':
                        foreach ($featureConditions as $value) {
                            $featureValue = new FeatureValue();
                            $featureValue->id_feature = $feature->id;
                            foreach (Language::getLanguages(false) as $lang) {
                                $featureValue->value[(int)$lang['id_lang']] = $value;
                            }
                            $featureValue->save();
                        }
                        break;

                    default:
                        # code...
                        break;
                }
            }
        }
        return true;
    }

    private function createBookstoreOrderState()
    {
        $orderState = new OrderState();

        foreach (Language::getLanguages(false) as $lang) {
            $orderState->name[(int)$lang['id_lang']] = $this->l('Pay in shop');
        }
        $orderState->module_name = $this->name;
        $orderState->invoice = true;
        $orderState->shipped = true;
        $orderState->paid = true;
        $orderState->pdf_delivery = false;
        $orderState->pdf_invoice = true;
        $orderState->color = '#9c7240';
        if ($orderState->save()) {
            Configuration::updateValue('PS_OS_EVERPSBOOKSTORE', (int)$orderState->id);
            return true;
        }
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');
        Configuration::deleteByName('EVERPSBOOKSTORE_CUSTOMERS_IDS');
        Configuration::deleteByName('EVERPSBOOKSTORE_ID_CARRIER');
        $this->deleteOrderState();
        return parent::uninstall();
    }

    private function uninstallModuleTab($tabClass)
    {
        $tab = new Tab((int)Tab::getIdFromClassName($tabClass));

        return $tab->delete();
    }

    private function deleteOrderState()
    {
        $orderState = new OrderState((int)Configuration::get('PS_OS_EVERPSBOOKSTORE'));

        if ($orderState->delete()) {
            return true;
        }
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->html = '';
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitEverpsbookstoreModule')) == true) {
            $this->postValidation();

            if (!count($this->postErrors)) {
                $this->postProcess();
            }
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        if (count($this->postErrors)) {
            foreach ($this->postErrors as $error) {
                $this->html .= $this->displayError($error);
            }
        }

        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/header.tpl');
        $this->html .= $this->renderForm();
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/footer.tpl');

        return $this->html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEverpsbookstoreModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $customers = Customer::getCustomers();
        $features = Feature::getFeatures(
            (int)Context::getContext()->language->id
        );
        $orderStates = OrderState::getOrderStates(
            (int)Context::getContext()->language->id
        );
        $carriers = Carrier::getCarriers(
            (int)Context::getContext()->language->id
        );
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-book',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'class' => 'chosen',
                        'multiple' => true,
                        'required' => true,
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'label' => $this->l('Super customer accounts'),
                        'desc' => $this->l('Will be allowed for creating and quick selling books'),
                        'hint' => $this->l('Please choose at least one customer account'),
                        'name' => 'EVERPSBOOKSTORE_CUSTOMERS_IDS',
                        'options' => array(
                            'query' => $customers,
                            'id' => 'id_customer',
                            'name' => 'email'
                        ),
                        'required' => true,
                    ),
                    array(
                        'type' => 'select',
                        'required' => true,
                        'label' => $this->l('Edition date feature'),
                        'name' => 'EVERPSBOOKSTORE_DATE_FEATURE',
                        'desc' => $this->l('Specify the edition date'),
                        'hint' => $this->l('Will be used for searching filters'),
                        'options' => array(
                            'query' => $features,
                            'id' => 'id_feature',
                            'name' => 'name'
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'required' => true,
                        'label' => $this->l('Condition feature'),
                        'name' => 'EVERPSBOOKSTORE_CONDITION_FEATURE',
                        'desc' => $this->l('Specify the condition feature (for searching new, refurbished...)'),
                        'hint' => $this->l('Will be used for searching filters'),
                        'options' => array(
                            'query' => $features,
                            'id' => 'id_feature',
                            'name' => 'name'
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'required' => true,
                        'label' => $this->l('Default carrier'),
                        'hint' => $this->l('Default carrier for super customer'),
                        'name' => 'EVERPSBOOKSTORE_ID_CARRIER',
                        'desc' => $this->l('Will be the only carrier allowed for payments in one click'),
                        'options' => array(
                            'query' => $carriers,
                            'id' => 'id_carrier',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'required' => true,
                        'label' => $this->l('Validated order state'),
                        'name' => 'EVERPSBOOKSTORE_VALIDATED_STATE_ID',
                        'desc' => $this->l('Specify the validated order state'),
                        'hint' => $this->l('Will send email to seller'),
                        'options' => array(
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ),
                        'required' => true,
                    ),
                    array(
                        'type' => 'select',
                        'class' => 'chosen',
                        'multiple' => true,
                        'required' => true,
                        'label' => $this->l('Cancelled order state'),
                        'name' => 'EVERPSBOOKSTORE_CANCELLED_STATE_IDS',
                        'desc' => $this->l('Specify the cancelled order state'),
                        'hint' => $this->l('Will cancel transaction between customer and seller'),
                        'options' => array(
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ),
                        'required' => true,
                    ),
                    array(
                        'type' => 'select',
                        'class' => 'chosen',
                        'multiple' => true,
                        'required' => true,
                        'label' => $this->l('Shipped order state'),
                        'name' => 'EVERPSBOOKSTORE_SHIPPED_STATE_IDS',
                        'desc' => $this->l('Specify the shipped order state'),
                        'hint' => $this->l('Won\'t send email from module'),
                        'options' => array(
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ),
                        'required' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'EVERPSBOOKSTORE_CUSTOMERS_IDS[]' => json_decode(
                Configuration::get('EVERPSBOOKSTORE_CUSTOMERS_IDS')
            ),
            'EVERPSBOOKSTORE_VALIDATED_STATE_ID' => Tools::getValue('EVERPSBOOKSTORE_VALIDATED_STATE_ID'),
            'EVERPSBOOKSTORE_CANCELLED_STATE_IDS[]' => json_decode(
                Configuration::get('EVERPSBOOKSTORE_CANCELLED_STATE_IDS')
            ),
            'EVERPSBOOKSTORE_SHIPPED_STATE_IDS[]' => json_decode(
                Configuration::get('EVERPSBOOKSTORE_SHIPPED_STATE_IDS')
            ),
            'EVERPSBOOKSTORE_ID_CARRIER' => Configuration::get('EVERPSBOOKSTORE_ID_CARRIER'),
            'EVERPSBOOKSTORE_DATE_FEATURE' => Configuration::get('EVERPSBOOKSTORE_DATE_FEATURE'),
            'EVERPSBOOKSTORE_CONDITION_FEATURE' => Configuration::get('EVERPSBOOKSTORE_CONDITION_FEATURE'),
        );
    }

    public function postValidation()
    {
        if (Tools::isSubmit('submitEverPsShopPaymentConf')) {
            if (!Tools::getvalue('EVERPSBOOKSTORE_ID_CARRIER')
                || !Validate::isUnsignedInt(Tools::getValue('EVERPSBOOKSTORE_ID_CARRIER'))
            ) {
                $this->postErrors[] = $this->l('Error : The field "Carrier" is not valid');
            }
            if (!Tools::getValue('EVERPSBOOKSTORE_CUSTOMERS_IDS')
                || !Validate::isArrayWithIds(Tools::getValue('EVERPSBOOKSTORE_CUSTOMERS_IDS'))
            ) {
                $this->postErrors[] = $this->l('Error : The field "Super customers" is not valid');
            }
            if (!Tools::getValue('EVERPSBOOKSTORE_VALIDATED_STATE_ID')
                || !Validate::isArrayWithIds(Tools::getValue('EVERPSBOOKSTORE_VALIDATED_STATE_ID'))
            ) {
                $this->postErrors[] = $this->l('Error : The field "Validated states" is not valid');
            }
            if (!Tools::getValue('EVERPSBOOKSTORE_CANCELLED_STATE_IDS')
                || !Validate::isArrayWithIds(Tools::getValue('EVERPSBOOKSTORE_CANCELLED_STATE_IDS'))
            ) {
                $this->postErrors[] = $this->l('Error : The field "Cancelled states" is not valid');
            }
            if (!Tools::getValue('EVERPSBOOKSTORE_SHIPPED_STATE_IDS')
                || !Validate::isArrayWithIds(Tools::getValue('EVERPSBOOKSTORE_SHIPPED_STATE_IDS'))
            ) {
                $this->postErrors[] = $this->l('Error : The field "Shipped states" is not valid');
            }
            if (!Tools::getValue('EVERPSBOOKSTORE_DATE_FEATURE')
                || !Validate::isUnsignedInt(Tools::getValue('EVERPSBOOKSTORE_DATE_FEATURE'))
            ) {
                $this->postErrors[] = $this->l('Error : The field "Date feature" is not valid');
            }
            if (!Tools::getValue('EVERPSBOOKSTORE_CONDITION_FEATURE')
                || !Validate::isUnsignedInt(Tools::getValue('EVERPSBOOKSTORE_CONDITION_FEATURE'))
            ) {
                $this->postErrors[] = $this->l('Error : The field "Condition feature" is not valid');
            }
        }
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        Configuration::updateValue(
            'EVERPSBOOKSTORE_ID_CARRIER',
            Tools::getValue('EVERPSBOOKSTORE_ID_CARRIER')
        );
        Configuration::updateValue(
            'EVERPSBOOKSTORE_CUSTOMERS_IDS',
            json_encode(Tools::getValue('EVERPSBOOKSTORE_CUSTOMERS_IDS')),
            true
        );
        Configuration::updateValue(
            'EVERPSBOOKSTORE_VALIDATED_STATE_ID',
            Tools::getValue('EVERPSBOOKSTORE_VALIDATED_STATE_ID')
        );
        Configuration::updateValue(
            'EVERPSBOOKSTORE_CANCELLED_STATE_IDS',
            json_encode(Tools::getValue('EVERPSBOOKSTORE_CANCELLED_STATE_IDS')),
            true
        );
        Configuration::updateValue(
            'EVERPSBOOKSTORE_SHIPPED_STATE_IDS',
            json_encode(Tools::getValue('EVERPSBOOKSTORE_SHIPPED_STATE_IDS')),
            true
        );
        Configuration::updateValue(
            'EVERPSBOOKSTORE_DATE_FEATURE',
            Tools::getValue('EVERPSBOOKSTORE_DATE_FEATURE')
        );
        Configuration::updateValue(
            'EVERPSBOOKSTORE_CONDITION_FEATURE',
            Tools::getValue('EVERPSBOOKSTORE_CONDITION_FEATURE')
        );
        EverPsBookstoreSeller::cleanBookstoreSellers(
            (int)Context::getContext()->shop->id
        );
    }

    /**
     * Hook payment, PS 1.7 only.
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if ((bool)EverPsBookstoreSeller::isBookstoreSeller() === false) {
            return;
        }
        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;
        $newOption->setModuleName($this->name)
        ->setCallToActionText($this->l('Pay in our shop'))
        ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
        ->setAdditionalInformation(
            $this->fetch('module:everpsbookstore/views/templates/front/payment_infos.tpl')
        );
        return array($newOption);
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }
        if ((bool)EverPsBookstoreSeller::isBookstoreSeller() === false) {
            return;
        }
        $this->smarty->assign(array(
            'shop_name' => Configuration::get('PS_SHOP_NAME'),
            'shop_phone' => Configuration::get(
                'PS_SHOP_PHONE',
                null,
                null,
                (int)Context::getContext()->shop->id
            ),
            'shop_email' => Configuration::get(
                'PS_SHOP_EMAIL',
                null,
                null,
                (int)Context::getContext()->shop->id
            ),
        ));

        return $this->fetch('module:everpsbookstore/views/templates/hook/payment_return.tpl');
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addCSS($this->_path.'views/css/ever.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        if ((bool)EverPsBookstoreSeller::isBookstoreSeller() === false) {
            return;
        }
        $this->context->controller->addJS($this->_path.'/views/js/everbookstore.js');
        $this->context->controller->addCSS($this->_path.'/views/css/everpsbookstore.css');
        if (Tools::isSubmit('everbookstore_validate_cart')) {
            if ((int)Context::getContext()->cart->id <= 0) {
                return;
                // Todo, add to cart before validate order. here cart is only created if does not exist
                $cart = new Cart();
                $cart->id_customer = Context::getContext()->customer->id;
                $cart->id_address_delivery = Address::getFirstCustomerAddressId(
                    (int)Context::getContext()->customer->id
                );
                $cart->id_address_invoice = Address::getFirstCustomerAddressId(
                    (int)Context::getContext()->customer->id
                );
                $cart->id_currency = Context::getContext()->currency->id;
                $cart->id_lang = Context::getContext()->language->id;
                $cart->add();
                Context::getContext()->cart = $cart;
                Context::getContext()->cookie->id_cart = $cart->id;
            }
            $cart_qty = Cart::getNbProducts(
                (int)Context::getContext()->cart->id
            );
            $total = Cart::getTotalCart(
                (int)Context::getContext()->cart->id
            );
            if ($cart_qty <= 0) {
                return;
                // Todo, add to cart before validate order
                $this->addToCart(
                    (int)Context::getContext()->cart->id,
                    (int)Tools::getValue('ever_id_product'),
                    (int)1,
                    (int)Context::getContext()->shop->id
                );
            }
            $payment_method_id = (int)Configuration::get('EVERPSBOOKSTORE_VALIDATED_STATE_ID');
            $id_cart = (int)Context::getContext()->cart->id;
            $this->validateOrder(
                (int)Context::getContext()->cart->id,
                $payment_method_id,
                $total,
                $this->displayPaymentName,
                null,
                null,
                (int)Context::getContext()->currency->id,
                false,
                Context::getContext()->customer->secure_key
            );
            Tools::redirect(
                'index.php?controller=order-confirmation&id_cart='
                .(int)$id_cart
                .'&id_module='
                .(int)Module::getModuleIdByName($this->name)
                .'&id_order='
                .$this->currentOrder
                .'&key='.Context::getContext()->customer->secure_key
            );
        }
    }

    public function hookDisplayLeftColumn()
    {
        if ((bool)EverPsBookstoreSeller::isBookstoreSeller() === false) {
            return;
        }
        return $this->hookDisplayRightColumn();
    }

    public function hookDisplayRightColumn()
    {
        if ((bool)EverPsBookstoreSeller::isBookstoreSeller() === false) {
            return;
        }
        if ((bool)Context::getContext()->customer->isLogged()) {
            $customer = new Customer(
                (int)Context::getContext()->customer->id
            );
            $bookstore_seller = EverPsBookstoreSeller::getBookstoreSellerByCustomerId(
                (int)$customer->id
            );
            // If customer is not super customer, return
            if (!Validate::isLoadedObject($bookstore_seller)
                || (int)$bookstore_seller->id <= 0
            ) {
                return;
            }
            return $this->display(__FILE__, 'views/templates/hook/columns.tpl');
        }
    }

    public function hookDisplayCustomerAccount()
    {
        if ((bool)Context::getContext()->customer->isLogged() === false) {
            return;
        }
        if ((bool)EverPsBookstoreSeller::isBookstoreSeller() === false) {
            return;
        }
        $customer = new Customer(
            (int)Context::getContext()->customer->id
        );
        $bookstore_seller = EverPsBookstoreSeller::getBookstoreSellerByCustomerId(
            (int)$customer->id
        );
        // If customer is not super customer, return
        if (!Validate::isLoadedObject($bookstore_seller)
            || (int)$bookstore_seller->id <= 0
        ) {
            return;
        }
        return $this->display(__FILE__, 'views/templates/hook/myaccount.tpl');
    }

    public function hookDisplayReassurance($params)
    {
        if (!Tools::getValue('id_product')) {
            return;
        }
        if ((bool)EverPsBookstoreSeller::isBookstoreSeller() === false) {
            return;
        }
        if ((bool)Context::getContext()->customer->isLogged() === false) {
            return;
        }
        $customer = new Customer(
            (int)Context::getContext()->customer->id
        );
        $bookstore_seller = EverPsBookstoreSeller::getBookstoreSellerByCustomerId(
            (int)$customer->id
        );
        // If customer is not super customer, return
        if (!Validate::isLoadedObject($bookstore_seller)
            || (int)$bookstore_seller->id <= 0
        ) {
            return;
        }
        $product = new Product(
            (int)Tools::getValue('id_product')
        );
        $this->context->smarty->assign(array(
            'product' => $product
        ));
        return $this->display(__FILE__, 'views/templates/hook/edit_button.tpl');
    }

    public function hookDisplayProductPriceBlock($params)
    {
        if ($params['type'] != 'before_price') {
            return;
        }
        if ((bool)Context::getContext()->customer->isLogged() === false) {
            return;
        }
        if ((bool)EverPsBookstoreSeller::isBookstoreSeller() === false) {
            return;
        }
        $customer = new Customer(
            (int)Context::getContext()->customer->id
        );
        $bookstore_seller = EverPsBookstoreSeller::getBookstoreSellerByCustomerId(
            (int)$customer->id
        );
        // If customer is not super customer, return
        if (!Validate::isLoadedObject($bookstore_seller)
            || (int)$bookstore_seller->id <= 0
        ) {
            return;
        }
        $product = new Product(
            (int)$params['product']->id
        );
        $this->context->smarty->assign(array(
            'product' => $product
        ));
        return $this->display(__FILE__, 'views/templates/hook/edit_button.tpl');
    }

    public function hookUpdateCarrier($params)
    {
        $carrier = $params['carrier'];
        $id_carrier_old = (int)($params['id_carrier']);
        $id_carrier_new = (int)$carrier->id;
        if ((int)$id_carrier_old == (int)(Configuration::get('EVERPSBOOKSTORE_ID_CARRIER'))) {
            Configuration::updateValue(
                'EVERPSBOOKSTORE_ID_CARRIER',
                (int)$id_carrier_new
            );
        }
    }

    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $customer = new Customer(
            (int)$order->id_customer
        );
        $bookstore_seller = EverPsBookstoreSeller::getBookstoreSellerByCustomerId(
            (int)$customer->id
        );
        // If customer is not super customer, return
        if (!Validate::isLoadedObject($bookstore_seller)
            || (int)$bookstore_seller->id <= 0
        ) {
            return;
        }
        $order = $params['order'];
        if (Validate::isLoadedObject($order)) {
            // here auto update order state if super customer
        }
    }

    public function hookDisplayOrderConfirmation($params)
    {
        if ($this->isSeven) {
            $order = $params['order'];
        } else {
            $order = $params['objOrder'];
        }
        $customer = new Customer(
            (int)$order->id_customer
        );
        $bookstore_seller = EverPsBookstoreSeller::getBookstoreSellerByCustomerId(
            (int)$customer->id
        );
        // If customer is not super customer, return
        if (!Validate::isLoadedObject($bookstore_seller)
            || (int)$bookstore_seller->id <= 0
        ) {
            return;
        }
        $this->context->smarty->assign(array(
            'everimg_dir' => $this->_path.'views/img'
        ));
        return $this->display(__FILE__, 'views/templates/hook/orderconfirmation.tpl');
    }

    public function hookActionObjectProductUpdateAfter($params)
    {
        if (Context::getContext()->controller->controller_type != 'front') {
            return;
        }
        $product = new Product(
            (int)$params['object']->id
        );
        if (!empty($product->isbn)) {
            // Get datas from ISBN
        }
        // If stock is set to 0, disable product and redirect 301 to parent category, beware of product attributes
        if ($product->hasAttributes()) {
            $attr_resumes = $product->getAttributesResume(
                (int)Context::getContext()->language->id
            );
            $stock = 0;
            foreach ($attr_resumes as $attr_resume) {
                $stock += (int)StockAvailable::getStockAvailableIdByProductId(
                    (int)$attr_resume['id_product'],
                    (int)$attr_resume['id_product_attribute'],
                    (int)Context::getContext()->shop->id
                );
            }
            if ((int)$stock <= 0) {
                EverPsBookstoreTools::autoDisableRedirectBook(
                    (int)$product->id,
                    (int)Context::getContext()->shop->id
                );
            }
        } else {
            EverPsBookstoreTools::autoDisableRedirectBook(
                (int)$product->id,
                (int)Context::getContext()->shop->id
            );
        }
    }

    public function hookActionObjectCustomerDeleteAfter($params)
    {
        $bookstore_seller = EverPsBookstoreSeller::getBookstoreSellerByCustomerId(
            (int)$params['object']->id
        );
        if (Validate::isLoadedObject($bookstore_seller)
            && (int)$bookstore_seller->id > 0
        ) {
            $bookstore_seller->delete();
        }
    }

    /**
     * addToCart in ps_cart_product table
     * @param int $id_cart
     * @param int $id_product
     * @param int $id_shop
     * @return bool result
     */
    public function addToCart($id_cart, $id_product, $qty, $id_shop)
    {
        if ((int)$qty < 1) {
            $qty = 1;
        }
        $date = new DateTime();
        $mysqltime =  $date->format('Y-m-d H:i:s');

        $sql = new DbQuery();
        $sql->select('quantity');
        $sql->from('cart_product');
        $sql->where('id_product = ' . $id_product);
        $sql->where('id_cart = ' . $id_cart);
        $sql->where('id_shop = ' . $id_shop);

        $quantity = Db::getInstance()->getValue($sql);

        if ($quantity) {
            $quantity += $qty;
            Db::getInstance()->update(
                'cart_product',
                array(
                    "quantity" => $quantity
                ),
                "id_cart = " . $id_cart . " AND id_product = " . $id_product . " AND id_shop = " . $id_shop
            );
            Hook::exec('actionCartSave');
        } else {
            Db::getInstance()->insert(
                'cart_product',
                array(
                    'id_cart' => $id_cart,
                    'id_product' => $id_product,
                    'id_address_delivery' => 0,
                    'id_shop' => $id_shop,
                    'id_product_attribute' => 0,
                    'quantity' => $qty,
                    'date_add' => $mysqltime
                )
            );
            Hook::exec('actionCartSave');
        }
    }
}
