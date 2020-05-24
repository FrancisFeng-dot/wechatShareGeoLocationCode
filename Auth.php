<?php
namespace app\api\controller;
use think\Controller;
use think\Request;
use think\View;
use think\Session;
use think\Db;
use think\Loader;
use think\cache\driver\Redis;
class Auth extends Init
{
public function mobile_indexb(){
  $view = new View();
    $signPackage = get_signature();//自定义分享方法,获取签名值数组 
    $news = array("Title" =>"第十三届中国视频大赛", "Description"=>"第十三届中国视频大赛深圳赛区赛事介绍", "PicUrl" =>'http://www.shipin.cn/img/hnklogo.jpg', "Url" =>'http://www.shipin.cn/registration.html');
   $view->assign('signPackage',$signPackage);
   $view->assign('news',$news);
        return $view->fetch('mobile_indexb');}

}

































































}
