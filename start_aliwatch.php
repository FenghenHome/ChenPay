<?php 
//TODO:警报邮件发送数量限制
use Workerman\Worker;
use think\Db;
require_once __DIR__ . '/vendor/autoload.php';
$GLOBALS['endtime'] = 10;
$GLOBALS['aliSum'] = 1;
// 支付宝接口切换
$GLOBALS['aliType'] = true;
// 暂停 有订单情况下才是10秒一次的频率 杜绝支付宝风控
$GLOBALS['aliStatus'] = time();
function curl_post_https($url,$data){ 
    $url = preg_replace('/([^:])[\/\\\\]{2,}/','$1/',$url);
	$urlfields = "";
	foreach($data as $k => $v ){
		$urlfields .= $k . "=" . urlencode($v) . "&";
	}
	$url = rtrim($url.'?'.$urlfields,'&');
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url); 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
    $tmpInfo = curl_exec($curl); // 执行操作
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据
}
$worker = new Worker();
$worker->count = 1;
$worker->name = 'Alipay Watch';
$worker->onWorkerStart = function($worker)
{
	Db::setConfig(['type'=> 'sqlite','database'=> __DIR__.'/database.db','prefix'=> '','debug'=> true]);
    \Workerman\Lib\Timer::add(30, function(){
		$data = Db::table('payinfo')->where('pay_way','alipay')->select();
		include __DIR__.'/config.php';
        if ($GLOBALS['aliStatus'] > time() && count($data) == 0) return;
        try {
            $run = (new ChenPay\AliPay($aliCookie))->getData($GLOBALS['aliType'])->DataHandle();
            foreach ($data as $item) {
                $order = $run->DataContrast($item['fee'], $item['time'], $GLOBALS['endtime'], $item['remarks']);
                if ($order){
					$PaySign = md5($AppID.$item['remarks'].$item['fee'].$order.'alipay'.$AppKey);
					$NotifyReturn = curl_post_https($item["notifyurl"],array('remarks' => $item['remarks'],'fee' => $item['fee'],'orderid' => $order,'pay_way' => 'alipay','sign' => $PaySign));
					if(trim($NotifyReturn) == 'success'){
						//异步请求成功
						Db::table('payinfo')->where('id',$item['id'])->delete();
					}else{
						throw new \ChenPay\PayException\PayException("订单号{$order} 订单备注{$item['remarks']}异步上报失败,".trim($NotifyReturn), 500);
					}
	    		}
                unset($order, $item);
            }
            $GLOBALS['aliType'] = !$GLOBALS['aliType'];
            $GLOBALS['aliSum']++;
            $GLOBALS['aliStatus'] = time() + 2 * 60;
        } catch (\ChenPay\PayException\PayException $e) {
			//错误处理
		    $ErrorMsg = '['.date("Y-m-d H:i:sa").'] Error: '.$e->getMessage().PHP_EOL;
			file_put_contents(__DIR__.'/error.log',$ErrorMsg,FILE_APPEND);
            if($ErrorEmailReport){
				$ReturnSEND = aliwatch_sendemail($SmtpAddress,$SmtpPort,$SmtpSSL,$SmtpUsername,$SmtpPassword,$SendEmail,'[ChenPay]运行时遇到错误',$ErrorMsg,$SmtpForm);
				if($ReturnSEND['result'] != 'success'){
					file_put_contents(__DIR__.'/error.log','['.date("Y-m-d H:i:sa").'] Error: 邮件发送错误:'.$ReturnSEND['msg'].PHP_EOL,FILE_APPEND);
				}
			}
            unset($e);
        }
        unset($run, $data);
		Db::table('payinfo')->where('time','<',time())->delete();
    });
};
if(!function_exists('aliwatch_sendemail')){
function aliwatch_sendemail($smtpaddress,$smtpport,$smtpssl,$smtpusername,$smtppassword,$sendto,$subject,$body,$smtpform){
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->CharSet = "UTF-8";
        $mail->Host = $smtpaddress;
        $mail->Port = $smtpport;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpusername;
        $mail->Password = $smtppassword;
		$mail->setFrom($smtpform, 'ChenPay');
        $mail->SMTPSecure = false;
        if ($smtpssl != 'none') {
			if($smtpssl != 'ssl' && $smtpssl != 'tls'){
				return array("result" => "error", "msg" => 'SSL类别错误');
			}
            $mail->SMTPSecure = $smtpssl;
            $mail->SMTPOptions = array("ssl" => array("verify_peer" => false, "verify_peer_name" => false, "allow_self_signed" => true));
        }
        $mail->addAddress($sendto);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
    } catch (PHPMailer\PHPMailer\Exception $e) {
        return array("result" => "error", "msg" => $e->getMessage());
    }
	return array("result" => "success");
}
}
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
