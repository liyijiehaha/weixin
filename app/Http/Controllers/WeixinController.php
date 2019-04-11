<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Weixinmodel;
class WeixinController extends Controller
{
    public function list(){
        echo $_GET['echostr'];
    }
    public function WxEvent(){
        $content = file_get_contents("php://input");
        $data=simplexml_load_string($content);
        echo 'ToUserName:'.$data->ToUserName;echo '<br>';
        echo 'FromUserName:'.$data->FromUserName;echo '<br>';
        echo 'CreateTime:'.$data->CreateTime;echo '<br>';
        echo 'MsgType:'.$data->MsgType;echo '<br>';
        echo 'Event:'.$data->Event;echo '<br>';
        echo 'EventKey:'.$data->EventKey;echo '<br>';
        $openid=$data->FromUserName;
        //获取用户信息
        $u=$this ->getUserInfo($openid);
         echo '<pre>';print_r($u);echo '</pre>';
         //用户信息入库
       $u_info=[
           'openid'=>$u['openid'],
           'nickname'=>$u['nickname'],
           'sex'=>$u['sex'],
           'headimgurl'=>$u['headimgurl'],
       ];
       $Weixin_model=new Weixinmodel();
       $res= $Weixin_model->insert($u_info);
      if($res){
            //TODO 保存成功;
          echo 'ok';
      }else{
          //TODO 保存失败;
          echo 'no ok ';
      }
        $time = date('Y-m-d H:i:s');
        is_dir('logs')or mkdir('logs',0777,true);
        $str = $time.$content."\n";
        file_put_contents("logs/wx_event.log",$str,FILE_APPEND);
        echo "success";
    }
    public function getaccesstoken(){
        $key='wx_assess_token';
        $token=Redis::get($key);
        if($token){
            echo '有';
        }else{
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_APPSECRET');
            $response=file_get_contents($url);
            $arr =json_decode($response,true);
            Redis::set($key,$arr['access_token']);
            Redis::expire($key,3600);
            $token=$arr['access_token'];
        }
        return $token;
    }
    /*
     *获取微信用户
     * */
    public function getUserInfo($openid){
        $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->getaccesstoken()."&openid=".$openid."&lang=zh_CN";
        $data=file_get_contents($url);
        $u=json_decode($data,true);
        return $u;
    }

}
