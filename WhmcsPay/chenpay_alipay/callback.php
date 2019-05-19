<?php
use WHMCS\Database\Capsule;
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewaymodule = "chenpay_alipay";
$GATEWAY       = getGatewayVariables($gatewaymodule);
if(!$GATEWAY["type"]){
	exit("fail");
}

$security['out_trade_no'] = str_replace(@$_REQUEST['remarks'][0].@$_REQUEST['remarks'][1].@$_REQUEST['remarks'][2],'',@$_REQUEST['remarks']);
$security['total_fee'] = @$_REQUEST['fee'];
$security['trade_no'] = @$_REQUEST['orderid'];
$Sign = md5($GATEWAY["appid"].@$_REQUEST['remarks'].@$_REQUEST['fee'].@$_REQUEST['orderid'].'alipay'.$GATEWAY["appsk"]);
//额外手续费
$fee = 0;
if($Sign == @$_REQUEST['sign']){
    $invoiceid = checkCbInvoiceID($security['out_trade_no'], $GATEWAY["name"]);
    checkCbTransID($security['trade_no']);
    addInvoicePayment($invoiceid,$security['trade_no'],trim($security['total_fee']),$fee,$gatewaymodule);
    logTransaction($GATEWAY["name"], $_REQUEST, "Successful");
    echo 'success';
} else {
    echo 'fail';
}