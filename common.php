<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  
// +----------------------------------------------------------------------
use think\Request;
use think\View;
use think\Db;
use think\Session;
use think\Validate;
use think\cache\driver\Redis;
// 配置验证器
/**
 * minishop md5加密方法
 * @author wyl  
 */
function yscheck($data)
{
    
/////////////微信自定义分享模块////////////////////////
//获取token
function get_token() {
    $info=config('Wx');
    $token=$info['token'];
    session ( 'token', $token );
    return $token;
}
// 获取access_token，自动带缓存功能
function get_access_token($token = '') {
    empty ($token) && $token = get_token();
    $model = Db::name("access_token");
    $map['token'] = $token;
    $info = $model->where($map)->find();
    if(!$info)
    {
        $newaccess_token = getNowAccesstoken($token);
    }
    else
    {
        $nowtime = time();//现在时间
        $time = $nowtime - $info['lasttime'];
        $newaccess_token = $info['access_token'];
        if($time >= 1800){
            $newaccess_token = getNowAccesstoken($token);
            if($newaccess_token == 0){//重新再 调用一次
                $newaccess_token = getNowAccesstoken($token);
            }
        }
    }

    return $newaccess_token;
}
function getNowAccesstoken($token = ''){
    $nowtime = time();//现在时间
    empty ( $token ) && $token = get_token ();
    $info = get_token_appinfo ($token);
    if (empty ($info ['appid'] ) || empty ($info['secret'])) {
        return 0;
    }
    $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $info ['appid'] . '&secret=' . $info ['secret'];
    $ch1 = curl_init ();
    $timeout = 5;
    curl_setopt ( $ch1, CURLOPT_URL, $url );
    curl_setopt ( $ch1, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $ch1, CURLOPT_CONNECTTIMEOUT, $timeout );
    curl_setopt ( $ch1, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt ( $ch1, CURLOPT_SSL_VERIFYHOST, false );
    $accesstxt = curl_exec ( $ch1 );
    curl_close ( $ch1 );
    $tempArr = json_decode ($accesstxt, true);
    // p($tempArr);
    if (!isset($tempArr['errmsg'])) {
        $model = Db::name("access_token");
        $map['token'] = $token;
        //保存新access_token到数据库，更新最后时间
        $data = array(
            'access_token'=>$tempArr ['access_token'],
            'lasttime'=>$nowtime
        );
        $info=$model->where($map)->find();
        if($info)
        {
            $model->where($map)->update($data);
        }
        else
        {
            $data['token'] = $token;
            $model->where($map)->insert($data);
        }
        return $tempArr ['access_token'];
    }else{
        return 0;
    }
}
// 获取jsapi_ticket，判断是不过期
function getJsapiTicket($token = '') {
    empty ($token) && $token = get_token();
    $model = Db::name("jsapi_ticket");
    $map['token'] = $token;
    $info = $model->where($map)->find();
    if(!$info)
    {
        $new_jsapi_ticket = getNowJsapiTicket($token);
    }
    else
    {
        $nowtime = time();//现在时间
        $time = $nowtime - $info['lasttime'];
        $new_jsapi_ticket = $info['ticket'];
        if($time>=1800){
            $new_jsapi_ticket = getNowJsapiTicket($token);
            if($new_jsapi_ticket == 0){//重新再 调用一次
                $new_jsapi_ticket = getNowJsapiTicket($token);
            }
        }
    }

    return $new_jsapi_ticket;
}
//获取jsapi_ticket
function getNowJsapiTicket($token='')
{
    empty ($token) && $token = get_token();
    $access_token=get_access_token();
    $url='https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' .$access_token. '&type=jsapi';
    $ch1 = curl_init ();
    $timeout = 5;
    curl_setopt ( $ch1, CURLOPT_URL, $url );
    curl_setopt ( $ch1, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $ch1, CURLOPT_CONNECTTIMEOUT, $timeout );
    curl_setopt ( $ch1, CURLOPT_SSL_VERIFYPEER, FALSE );
    curl_setopt ( $ch1, CURLOPT_SSL_VERIFYHOST, false );
    $accesstxt = curl_exec ( $ch1 );
    curl_close ( $ch1 );
    $tempArr = json_decode ($accesstxt, true);
    $ext=$tempArr['errmsg'];
    if ($ext=='ok') {
        $model = Db::name("jsapi_ticket");
        $map['token'] = $token;
        $nowtime=time();
        //保存新jsapi_ticket到数据库，更新最后时间
        $data = array(
            'ticket'=>$tempArr ['ticket'],
            'lasttime'=>$nowtime
        );
        $info=$model->where($map)->find();
        if($info)
        {
            $model->where($map)->update($data);
        }
        else
        {
            $data['token'] = $token;
            $model->where($map)->insert($data);
        }
        return $tempArr['ticket'];
    }
    else
    {
        return 0;
    }
}
// 获取公众号的信息
function get_token_appinfo() {
    $info=config('Wx');
    return $info;
}
//获取signature的值 获取签名值数组
function get_signature()
{
    $url='http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
   //$_SERVER["HTTP_HOST"]或者$_SERVER["SERVER_NAME"]多试试，重要
    $ticket=getJsapiTicket();
    $noncestr=createNonceStr();
    $timestamp=time();
    $string='jsapi_ticket='.$ticket.'&noncestr='.$noncestr.'&timestamp='.$timestamp.'&url='.$url;
    $signature = sha1($string);
    $signPackage = array(
        "appId"     =>config('Wx.appid'),
        "nonceStr"  =>$noncestr,
        "timestamp" => $timestamp,
        "url"       => $url,
        "signature" => $signature,
        "string" => $string,
        "jsapi_ticket" => $ticket,
    );
    return  $signPackage;
}
//随机生成字符串
 function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}
/////////////微信自定义分享模块////////////////////////