<?php

/**
 * @package    zarinpalwg payment module
 * @author     Masoud Amini
 * @copyright  2014  MasoudAmini.ir
 * @version    1.00
 */
@session_start();
if (isset($_GET['do'])) {
    include (dirname(__FILE__) . '/../../config/config.inc.php');
    include (dirname(__FILE__) . '/../../header.php');
    include_once (dirname(__FILE__) . '/zarinpalwg.php');
    $zarinpalwg = new zarinpalwg;
    if ($_GET['do'] == 'payment') {

        $zarinpalwg->do_payment($cart);
    } else {
        if (isset($_GET['id']) && isset($_GET['amount']) && isset($_GET['Authority']) && isset($_GET['Status'])) {
            $orderId = $_GET['id'];
            $amount = $_GET['amount'];
            if (isset($_SESSION['order' . $orderId])) {
                $hash = Configuration::get('zarinpalwg_HASH');
                $hash = md5($orderId . $amount . $hash);
                if ($hash == $_SESSION['order' . $orderId]) {
                    $api = Configuration::get('zarinpalwg_API');
$data = array('MerchantID' => $api, 'Authority' => $_GET["Authority"], 'Amount' => $amount);
$jsonData = json_encode($data);
$ch = curl_init('https://www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json');
curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
));

$result = curl_exec($ch);
curl_close($ch);
$result = json_decode($result, true);
                    

                    if (!empty($result['Status']) and $result['Status'] == 100) {
                        error_reporting(E_ALL);
                        $au = $_GET['Authority'];
                        $zarinpalwg->validateOrder($orderId, _PS_OS_PAYMENT_, $amount, $zarinpalwg->displayName, "سفارش تایید شده / کد رهگیری {$au}", array(), $cookie->id_currency);
                        $_SESSION['order' . $orderId] = '';
                        Tools::redirect('history.php');
                    } else {
                        echo $zarinpalwg->error($zarinpalwg->l('There is a problem.') . ' (' . $result['Status'] . ')<br/>' . $zarinpalwg->l('Authority code') . ' : ' . $_GET['Authority']);
                    }
                } else {
                    echo $zarinpalwg->error($zarinpalwg->l('There is a problem.'));
                }
            } else {
                echo $zarinpalwg->error($zarinpalwg->l('There is a problem.'));
            }
        } else {
            echo $zarinpalwg->error($zarinpalwg->l('There is a problem.'));
        }
    }
    include_once (dirname(__FILE__) . '/../../footer.php');
} else {
    _403();
}

function _403() {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}
