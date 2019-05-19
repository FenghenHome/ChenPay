<?php
use Workerman\Worker;
use think\Db;
require_once __DIR__ . '/vendor/autoload.php';
$http_worker = new Worker("http://0.0.0.0:23333");
$http_worker->name = 'Http Server';
$http_worker->count = 4;

Db::setConfig(['type'=> 'sqlite','database'=> __DIR__.'/database.db','prefix'=> '','debug'=> true]);
$http_worker->onMessage = function($connection, $data)
{
	include __DIR__.'/config.php';
    if(!@$_REQUEST['appid'] || !@$_REQUEST['remarks'] || !@$_REQUEST['fee'] || !@$_REQUEST['stoptime'] || !@$_REQUEST['notifyurl'] || !@$_REQUEST['sign'] || !@$_REQUEST['pay_way']){
		$connection->send(json_encode(array('code' => 500,'msg' => '参数不全')));
		return ;
	}
	if(Db::table('payinfo')->where('remarks',$_REQUEST['remarks'])->find() && (Db::table('payinfo')->where('remarks',$_REQUEST['remarks'])->find()->time > time())){
		if(@$_REQUEST['order_cache']){
           $connection->send(json_encode(array('code' => 200,'msg' => '备注允许重复,无需添加','lasttime' => (Db::table('payinfo')->where('remarks',$_REQUEST['remarks'])->find()->time) - time())));
		   return ;
		}else{
           $connection->send(json_encode(array('code' => 500,'msg' => '备注已重复')));
		   return ;
		}
	}
	if(trim($_REQUEST['pay_way']) != 'alipay' && trim($_REQUEST['pay_way']) != 'wechat'){
		$connection->send(json_encode(array('code' => 500,'msg' => '支付接口不存在')));
		return ;
	}
	$PaySign = md5($AppID.$_REQUEST['pay_way'].$_REQUEST['remarks'].$_REQUEST['fee'].$_REQUEST['stoptime'].$_REQUEST['notifyurl'].$AppKey);
	if(trim($_REQUEST['sign']) != $PaySign){
		$connection->send(json_encode(array('code' => 500,'msg' => 'Sign不正确')));
		return ;
	}
	if(!is_numeric(trim($_REQUEST['fee'])) || !is_numeric(trim($_REQUEST['stoptime']))){
		$connection->send(json_encode(array('code' => 500,'msg' => '过期日期及金额必须为number类型')));
		return ;
	}
	if(filter_var(trim($_REQUEST['notifyurl']), FILTER_VALIDATE_URL) === FALSE) {
        $connection->send(json_encode(array('code' => 500,'msg' => 'NotifyUrl错误')));
		return ;
    }
	if(!(Db::table('payinfo')->insert(["fee" => trim($_REQUEST['fee']),"time" => trim($_REQUEST['stoptime']),"remarks" => $_REQUEST['remarks'],"notifyurl" => $_REQUEST['notifyurl'],"pay_way" => $_REQUEST['pay_way']]))){
        $connection->send(json_encode(array('code' => 500,'msg' => '数据添加错误')));
		return ;
	}else{
		$connection->send(json_encode(array('code' => 200,'msg' => '订单添加成功')));
	}
};

if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}