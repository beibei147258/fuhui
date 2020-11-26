<?php
$time = intval(time());
$url = "http://api.gzgo360.com/gateway.do?m=order";

 $key  = "6528924B7C6BE975153C9B8965E29269";
 $req["merchno"] = "333410759630001";// 锐捷测试账号

$req["amount"] = $_REQUEST['amount'];

$req["traceno"] = $time;
$req["channel"] = "2";
$req["bankCode"] =  $_REQUEST['istype'];
$req["settleType"] = "2";

$req["notifyUrl"] = "/index/pay/wangyin";
$req["returnUrl"] = "http://zxyqq.club/index/index/mine/token/aaaaaa";


echo $md5Param = "amount=".$req["amount"]
."&bankCode=".$req["bankCode"]
."&channel="."2"
."&merchno=".$req["merchno"]
."&notifyUrl=".$req["notifyUrl"]
."&returnUrl=".$req["returnUrl"]
."&settleType=".$req["settleType"]
."&traceno=".$req["traceno"]
."&".$key;

echo $signature = strtoupper(md5($md5Param));
$req['signature'] = $signature;
//方便查看输出结果,换行一下
echo "\n";
//拼装URL请求参数，中文以GBK编码并URL转码
echo $param =
"&signature=".$signature
.$md5Param = "amount=".$req["amount"]
."&bankCode=".$req["bankCode"]
."&channel="."1"
."&merchno=".$req["merchno"]
."&notifyUrl=".$req["notifyUrl"]
."&returnUrl=".$req["returnUrl"]
."&settleType=".$req["settleType"]
."&traceno=".$req["traceno"];

// $res = http_request($url, $data);
// //方便查看输出结果,换行一下
// echo "\n";
// //将返回结果GBK转UTF-8输出显示
// echo $utf = iconv('GB2312','utf-8',$res);
?>

<html><head></head><body><form id='pay_form' name='pay_form' action='<?=$url?>' method='POST'>
    <input type='hidden' name='signature' value='<?=$req['signature'] ?>'	/>
    <input type='hidden' name='amount' value='<?=$req['amount'] ?>'	/>
    <input type='hidden' name='channel' value='<?=$req['channel'] ?>'	/>
    <input type='hidden' name='merchno' value='<?=$req['merchno'] ?>'	/>
    <input type='hidden' name='bankCode' value='<?=$req['bankCode'] ?>'	/>
    <input type='hidden' name='notifyUrl' value='<?=$req['notifyUrl'] ?>'	/>
    <input type='hidden' name='returnUrl' value='<?=$req['returnUrl'] ?>'	/>
    <input type='hidden' name='settleType' value='<?=$req['settleType'] ?>'	/>
    <input type='hidden' name='traceno' value='<?=$req['traceno'] ?>'	/>
</form>
<script language='javascript'>window.onload=function(){document.pay_form.submit();}</script>
</body></html>