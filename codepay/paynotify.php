<?php
/**
 * ---------------------通知异步回调接收页-------------------------------
 * 
 * 此页就是您之前传给http://pay.ebkf.net的notify_url页的网址
 * 支付成功，平台会根据您之前传入的网址，回调此页URL，post回参数
 * 
 * --------------------------------------------------------------
 */
require_once("inc.php");

    $price = $_POST["price"];
    $orderuid = $_POST["orderuid"];
	$orderuid1 = substr($orderuid,5);

	$time2=time();

 $sql1 = "select usermoney from wp_userinfo where username = '".$orderuid."'";
$retval = mysqli_query( $conn1, $sql1 );
$row1 = mysqli_fetch_array($retval);
$usermoney=$row1['usermoney'];
if(! $retval )
{
	exit;
    die('无法更新数据: ' . mysqli_error($conn1));
}

$sql12 = "update  wp_userinfo  set usermoney = usermoney +{$price} where username ='".$orderuid."'";
$retval2 = mysqli_query( $conn1, $sql12 );
if(! $retval2 )
{
	exit;
    die('无法更新数据: ' . mysqli_error($conn1));
}
$sql13 = "INSERT INTO wp_balance ".
			"(bptype,bptime,bpprice,remarks,uid,isverified,cltime,bpbalance) ".
				"VALUES ".
				"('1','".$time2."','".$price."','充值','".$orderuid1."','1','".$time2."','".$usermoney."')";
$retval3 = mysqli_query( $conn1, $sql13 );
if(! $retval3 )
{
exit;
die('无法更新数据: ' . mysqli_error($conn1));
}
echo "OK";
?>