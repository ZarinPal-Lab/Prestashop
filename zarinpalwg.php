<?php

/* * d
 * @package    zarinpalwg payment module
 * @author     Masoud Amini
 * @copyright  2014  MasoudAmini.ir
 * @version    1.00
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class zarinpalwg extends PaymentModule {

    private $_html = '';
    private $_postErrors = array();

    public function __construct() {

        $this->name = 'zarinpalwg';
        $this->tab = 'payments_gateways';
        $this->version = '1.1';
        $this->author = 'Masoud Amini';
        $this->currencies = true;
        $this->currencies_mode = 'radio';
        parent::__construct();
        $this->displayName = $this->l('zarinpal Payment Modlue');
        $this->description = $this->l('Online Payment With zarinpal');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
        if (!sizeof(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module');
        }
        $config = Configuration::getMultiple(array('zarinpalwg_API'));
        if (!isset($config['zarinpalwg_API'])) {
            $this->warning = $this->l('You have to enter your zarinpal merchant key to use zarinpalwg for your online payments.');
        }
    }

    public function install() {
        if (!parent::install() || !Configuration::updateValue('zarinpalwg_API', '') || !Configuration::updateValue('zarinpalwg_LOGO', '') || !Configuration::updateValue('zarinpalwg_HASH_KEY', $this->hash_key()) || !$this->registerHook('payment') || !$this->registerHook('paymentReturn')) {
            return false;
        } else {
            return true;
        }
    }

    public function uninstall() {
        if (!Configuration::deleteByName('zarinpalwg_API') || !Configuration::deleteByName('zarinpalwg_LOGO') || !Configuration::deleteByName('zarinpalwg_HASH_KEY') || !parent::uninstall()) {
            return false;
        } else {
            return true;
        }
    }

    public function hash_key() {
        $en = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
        $one = rand(1, 26);
        $two = rand(1, 26);
        $three = rand(1, 26);
        return $hash = $en[$one] . rand(0, 9) . rand(0, 9) . $en[$two] . $en[$tree] . rand(0, 9) . rand(10, 99);
    }

    public function getContent() {

        if (Tools::isSubmit('zarinpalwg_setting')) {

            Configuration::updateValue('zarinpalwg_API', $_POST['zp_API']);
            Configuration::updateValue('zarinpalwg_LOGO', $_POST['zp_LOGO']);
            $this->_html .= '<div class="conf confirm">' . $this->l('Settings updated') . '</div>';
        }

        $this->_generateForm();
        return $this->_html;
    }

    private function _generateForm() {
        $this->_html .= '<div align="center"><form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
        $this->_html .= $this->l('Enter your pin :') . '<br/><br/>';
        $this->_html .= '<input type="text" name="zp_API" value="' . Configuration::get('zarinpalwg_API') . '" ><br/><br/>';
        $this->_html .= '<input type="submit" name="zarinpalwg_setting"';
        $this->_html .= 'value="' . $this->l('Save it!') . '" class="button" />';
        $this->_html .= '</form><br/></div>';
    }

    public function do_payment($cart) {




        $client = new SoapClient('https://www.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);

        $ZarinpalPin = Configuration::get('zarinpalwg_API');
        $amount = floatval(number_format($cart->getOrderTotal(true, 3), 2, '.', ''));
        $callbackUrl = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__ . 'modules/zarinpalwg/zp.php?do=call_back&id=' . $cart->id . '&amount=' . $amount;
        $orderId = $cart->id;
        $txt = 'پرداخت سفارش شماره: ' . $cart->id;
        
        
        $data = array('MerchantID' => $ZarinpalPin, 'Amount' => $amount, 'CallbackURL' => $callbackUrl,'Mobile' => $Mobile,  'Email' => $Email , 'Description' => $txt);
$jsonData = json_encode($data);
$ch = curl_init('https://www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json');
curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
));

$result = curl_exec($ch);
$err = curl_error($ch);
$result = json_decode($result, true);
curl_close($ch);


        $hash = Configuration::get('zarinpalwg_HASH');
        $_SESSION['order' . $orderId] = md5($orderId . $amount . $hash);
        if ($result["Status"] == '') {
            echo $this->error($this->l('There is a problem.'));
        } elseif ($result["Status"] < 1) {
            echo $this->error($this->l('There is a problem.') . ' (' . $result["Status"] . ')');
        } else {
            echo $this->success($this->l('Redirecting...'));
            echo '<script>window.location=("https://www.zarinpal.com/pg/StartPay/' . $result["Authority"] . '");</script>';
        }
    }

    public function error($str) {
        return '<div class="alert error">' . $str . '</div>';
    }

    public function success($str) {
        echo '<div class="conf confirm">' . $str . '</div>';
    }

    public function hookPayment($data) {
        global $smarty;
        $smarty->assign('zarinpalwg_logo', Configuration::get('zarinpalwg_LOGO'));
        if ($this->active) {
            return $this->display(__FILE__, 'zppayment.tpl');
        }
    }

    public function hookPaymentReturn($data) {
        if ($this->active) {
            return $this->display(__FILE__, 'zpconfirmation.tpl');
        }
    }

}

// End of: zarinpalwg.php
