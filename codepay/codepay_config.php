<?php

error_reporting(E_ALL & ~E_NOTICE); //过滤脚本提醒
date_default_timezone_set('PRC'); //时区设置 解决某些机器报错
$codepay_config['id'] = '177607';
/**
 * MD5密钥，安全检验码，由数字和字母组成字符串，需要跟服务端一致
 * 设置地址：https://codepay.fateqq.com/admin/#/dataSet.html
 * 该值非常重要 请不要泄露 否则会影响支付的安全。 如泄露请重新到云端设置
 */
$codepay_config['key'] = 'GJDPDh6z2TXdG5sl8TnybIf3ETx7cQbO';

//字符编码格式 目前支持 gbk GB2312 或 utf-8 保证跟文档编码一致 建议使用utf-8
$codepay_config['chart'] = strtolower('utf-8');
header('Content-type: text/html; charset=' . $codepay_config['chart']);

//是否启用免挂机模式 1为启用. 未开通请勿更改否则资金无法及时到账
$codepay_config['act'] = '0'; //认证版则开启 一般情况都为0

/**订单支付页面显示方式
 * 3：自定义开发模式 (默认 复杂 需要一定开发能力  codepay.php修改收银台代码)
 * 4：高级模式(复杂 需要较强的开发能力   codepay.php修改收银台代码)
 */
$codepay_config['page'] = 4; //支付页面展示方式

//支付页面风格样式 仅针对$codepay_config['page'] 参数为 1或2 才会有用。
$codepay_config['style'] = 1; //暂时保留的功能 后期会生效 留意官网发布的风格编号


//二维码超时设置  单位：秒
$codepay_config['outTime'] = 360;//360秒=6分钟 最小值60  不建议太长 否则会影响其他人支付

//最低金额限制
$codepay_config['min'] = 0.01;

//启用支付宝官方接口 会员版授权后生效
$codepay_config['pay_type'] = 1;


$codepay_config['user'] = ''; //这是默认的充值用户 因为我们演示的数据库充值 只有该用户名 如正式使用请为空

$codepay_config['userOff'] = false; //这里设置是否显示出来用户输入用户名 除非你知道了如何获取到用户 否则不要更改

define('HTTPS', false);  //是否HTTPS站点 false为HTTP true为HTTPS


//主动判断是否HTTPS
function isHTTPS()
{
    if (defined('HTTPS') && HTTPS) return true;
    if (!isset($_SERVER)) return FALSE;
    if (!isset($_SERVER['HTTPS'])) return FALSE;
    if ($_SERVER['HTTPS'] === 1) {  //Apache
        return TRUE;
    } elseif ($_SERVER['HTTPS'] === 'on') { //IIS
        return TRUE;
    } elseif ($_SERVER['SERVER_PORT'] == 443) { //其他
        return TRUE;
    }
    return FALSE;
}

$codepay_config['gateway'] = '';  //设置支付网关

$codepay_config['host'] = (isHTTPS() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']; //获取域名

$codepay_config['path'] = $codepay_config['host'] . dirname($_SERVER['REQUEST_URI']); //API安装路径 最终为http://域名/codepay


//二维码本地实现 传入http://baidu.com 会加载http://baidu.com/?money=1&tag=0&type=1
// qrcode.php 为我们的演示控制二维码程序 删除下行注释【//】可以启用本地二维码 二维码上传至qr 目录下的1 2 3
// $codepay_config['qrcode_url'] = $codepay_config['path'].'/qrcode.php';

/**
 * 同步通知设置：
 * 同步通知用户关闭网页后则不会通知 通知地址公开的
 * 返回的参数通过MD5加密处理 返回target参数为get 则为同步通知数据
 * 设置通知地址不能附带任何参数，否则您需要自行验证签名或自行在验证数据签名前将$_GET['同步地址中的参数名']去掉
 * 以下为设置同步地址：(绝对路径)
 * http://你的域名/codepay/return.php
 */
$codepay_config['return_url'] = $codepay_config['path'] . '/notify.php'; //自动生成跳转地址


//可以删除下面【//】改成自己的 最终为：

//$codepay_config['return_url'] ='';



//设置默认通知页面 3秒后跳转到首页

$codepay_config['go_time'] = 3; //3秒跳转页面 默认为首页

$codepay_config['go_url'] =  $_SERVER[''] == '80' ? '/' : '//'.$_SERVER['SERVER_NAME']; 

//可以删除下面【//】改成自己的  以下为跳转到百度的例子
//$codepay_config['go_url'] = ''; 






$codepay_config['notify_url'] = $codepay_config['path'] . '/notify.php'; 





define('ROOT_PATH', dirname(__FILE__)); 
define('DEBUG', true);  
define('LOG_PATH', ROOT_PATH . '/log.txt');  
define('DB_PREFIX', 'codepay');  


define('DB_HOST', 'localhost'); 
define('DB_USER', 'pan');  
define('DB_PWD', 'pan');
define('DB_NAME', 'panpan');  
define('DB_PORT', '3306');  

define('DB_AUTOCOMMIT', false);  
define('DB_ENCODE', $codepay_config['chart'] == 'utf-8' ? 'utf8' : $codepay_config['chart']);  



define('DB_USERTABLE', 'wp_userinfo');  
define('DB_USERMONEY', 'usermoney');  
define('DB_USERNAME', 'username');  


?>