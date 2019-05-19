<?php
use WHMCS\Database\Capsule;

function chenpay_alipay_MetaData() {
    return array(
        'DisplayName' => 'ChenPay(支付宝)',
        'APIVersion' => '1.1',
    );
}

function chenpay_alipay_config() {
    $configarray = array(
        "FriendlyName"  => array(
            "Type"  => "System",
            "Value" => "ChenPay(支付宝)"
        ),
        "appurl"  => array(
            "FriendlyName" => "支付接口地址",
            "Type"         => "text",
            "Size"         => "32",
			"Default"         => "http://xxx.com:23333",
        ),
        "appid"  => array(
            "FriendlyName" => "应用ID",
            "Type"         => "text",
            "Size"         => "32",
        ),
        "payqr"  => array(
            "FriendlyName" => "支付二维码(只保留二维码即可)",
            "Type"         => "text",
            "Size"         => "32",
        ),
        "appsk" => array(
            "FriendlyName" => "应用密钥",
            "Type"         => "text",
            "Size"         => "32",
        )
    );

    return $configarray;
}

function chenpay_alipay_link($params) {
	if(@$_REQUEST['getstatus'] == 'yes'){
		return '等待支付中';
	}
    if($_REQUEST['cpaysub'] == 'yes'){
	   $Nowtime = time();
	   $StopTime = $Nowtime + 6 * 60;
	   $RandomString = chr(rand(97, 122)).chr(rand(97, 122)).chr(rand(97, 122));
	   $PaySign = md5(trim($params['appid']).'alipay'.$RandomString.$params['invoiceid'].$params['amount'].$StopTime.$params['systemurl'].'/modules/gateways/chenpay_alipay/callback.php'.trim($params['appsk']));
	   $GetInfo = json_decode(chenpay_alipay_curl_post(trim($params['appurl']).'/?order_cache=true',array("appid"  => trim($params['appid']),"remarks"  => $RandomString.$params['invoiceid'],"fee"  => $params['amount'],"stoptime"  => $StopTime,"pay_way"  => 'alipay',"sign"  => $PaySign,"notifyurl"  => $params['systemurl'].'/modules/gateways/chenpay_alipay/callback.php')),true);
	   if(!$GetInfo){
		   exit('订单添加错误：服务器未返回任何有效信息');
	   }
	   if($GetInfo['code'] != 200){
		   exit('订单添加错误：'.$GetInfo['msg']);
	   }
	   $userdata = array();
	   include __DIR__ . "/chenpay_alipay/phpqrcode.php";
	   $userdata['qrcode'] = $params['payqr'];
	   $userdata['money'] = $params['amount'];
	   $userdata['remarks'] = $RandomString.$params['invoiceid'];
	   $userdata['make_time'] = date('Y-m-d H:i:s',$Nowtime);
	   $userdata['end_time'] = date('Y-m-d H:i:s',$StopTime);
	   if(@$GetInfo['lasttime']){
		   $userdata['outTime'] = $GetInfo['lasttime'];
	   }else{
		   $userdata['outTime'] = 6 * 60;
	   }
	   $userdata['outTime'] = 6 * 60;
	   $userdata['logoShowTime'] = 2;
	   exit(chenpay_alipay_makehtml(json_encode($userdata)));
	}
    if(stristr($_SERVER['PHP_SELF'],'viewinvoice')){
		return '<form method="post" id=\'cpaysub\'><input type="hidden" name="cpaysub" value="yes"></form><button type="button" class="btn btn-danger btn-block" onclick="document.forms[\'cpaysub\'].submit()">使用支付宝支付</button>';
    }else{
         return '<img style="width: 150px" src="'.$params['systemurl'].'/modules/gateways/chenpay_alipay/alipay.png" alt="支付宝支付" />';
    }

}

if(!function_exists("chenpay_alipay_makehtml")){
function chenpay_alipay_makehtml($userdata){
	$skin_raw = file_get_contents(__DIR__ . "/chenpay_alipay/themes.tpl");
    $skin_raw = str_replace('{$userdata}',$userdata,$skin_raw);
    return $skin_raw;
}
}

if(!function_exists("chenpay_alipay_curl_post")){
function chenpay_alipay_curl_post($url,$data){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $tmpInfo = curl_exec($curl);
    curl_close($curl);
    return $tmpInfo;
}
}