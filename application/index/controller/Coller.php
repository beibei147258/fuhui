<?php

namespace app\index\controller;

use think\Controller;
use think\Db;

class Coller extends Controller
{

    /**
     * 充值配置收益
     *  profit()
     */
    public function profit()
    {
        $balance = Db::name('balance')->field('bpid,bpprice,uid,cltime,scale')->where('bptype', 8)->select();
        $currentDate = time();
        if (!empty($balance)) {
            foreach ($balance as $key => $val) {
                if ($currentDate >= $val['cltime']) {
                    $user = db('userinfo')->field('usermoney')->where('uid', $val['uid'])->find();
                    $b_data['bptype'] = 7;
                    $b_data['bpprice'] = $val['bpprice'] * $val['scale'] / 100; //充值金额*收益比例
                    $update['usermoney'] = $b_data['bpbalance'] = $user['usermoney']*1 + $b_data['bpprice']*1;
                    $p_date['uid'] = $val['uid'];
                    $p_date['oid'] = $val['bpid'];
                    $p_date['type'] = 3;
                    $p_date['account'] = $b_data['bpprice'];
                    $p_date['title'] = '充值';
                    $p_date['content'] = '自动收益涨跌';
                    $p_date['time'] = time();
                    $p_date['nowmoney'] = $b_data['bpbalance'];
                    \db('price_log')->insert($p_date);
                    Db::name('balance')->where('bpid', $val['bpid'])->update($b_data);
                    Db::name('userinfo')->where('uid', $val['uid'])->update($update);
                }
            }
            return 1;
        } else {
            return -1;
        }
    }


    /**
     * 分配订单
     * @return [type] [description]
     */
    public function allotorder()
    {
        //查找以平仓未分配的订单  isshow字段
        $map['isshow'] = 0;
        $map['ostaus'] = 1;
        $map['selltime'] = array('<',time()-300);
        $list = db('order')->where($map)->limit(0,10)->select();
        if(!$list){return false;}
        foreach ($list as $k => $v) {
            $this->allotfee($v['uid'],$v['fee'],$v['is_win'],$v['oid'],$v['ploss']);
            db('order')->where('oid',$v['oid'])->update(array('isshow'=>1));

        }
    }

    public function allotfee($uid,$fee,$is_win,$order_id,$ploss)
    {
        $userinfo = db('userinfo');
        $user = $userinfo->field('uid,oid')->where('uid',$uid)->find();
        $myoids = myupoid($user['oid']);
        if(!$myoids){return -1;}
        $_fee = 0;
        $_feerebate = 0;
        $web_poundage = getconf('web_poundage');
        if($is_win == 1){
            $pay_fee = $ploss;
        }elseif($is_win == 2){
            $pay_fee = $fee;
        }else{
            return -1;
        }
        foreach ($myoids as $k => $v) {
            if($user['oid'] == $v['uid']){	//直接推荐者拿自己设置的比例
                $_fee = round($pay_fee * ($v["rebate"]/100),2);
                $_feerebate = round($fee*$web_poundage/100 * ($v["feerebate"]/100),2);
                echo $_feerebate;
            }else{		//他上级比例=本级-下级比例
                $_my_rebate = ($v["rebate"] - $myoids[$k-1]["rebate"]);
                if($_my_rebate < 0) $_my_rebate = 0;
                $_fee = round($pay_fee * ( $_my_rebate /100),2);
                $_my_feerebate = ($v["feerebate"]  - $myoids[$k-1]["feerebate"] );
                if($_my_feerebate < 0) $_my_feerebate = 0;
                $_feerebate = round($fee*$web_poundage/100 * ( $_my_feerebate /100),2);
            }
            //红利
            if($is_win == 1){	//客户盈利代理亏损
                if($_fee != 0){
                    $ids_fee = $userinfo->where('uid',$v['uid'])->setDec('usermoney', $_fee);
                }else{
                    $ids_fee = null;
                }
                $type = 2;
                $_fee = $_fee*-1;
            }elseif($is_win == 2){	//客户亏损代理盈利
                if($_fee != 0){
                    $ids_fee = $userinfo->where('uid',$v['uid'])->setInc('usermoney', $_fee);
                }else{
                    $ids_fee = null;
                }
                $type = 1;
            }elseif($is_win == 3){	//无效订单不做操作
                $ids_fee = null;
            }
            if($ids_fee){  //余额
                $nowmoney = $userinfo->where('uid',$v['uid'])->value('usermoney');
                set_price_log($v['uid'],$type,$_fee,'对冲','下线客户平仓对冲',$order_id,$nowmoney);
            }
            //手续费
            if($_feerebate != 0){
                $ids_feerebate = $userinfo->where('uid',$v['uid'])->setInc('usermoney', $_feerebate);
            }else{
                $ids_feerebate = null;
            }
            if($ids_feerebate){
                //余额
                $nowmoney = $userinfo->where('uid',$v['uid'])->value('usermoney');
                set_price_log($v['uid'],1,$_feerebate,'客户手续费','下线客户下单手续费',$order_id,$nowmoney);
            }
        }
    }


    /**
     * 获取产品数据
     */
    public function product(){
        $conf = getconf1();
        $conf['product_type'] == '1' ? $this->officialData() : $this->randData();
    }

    /**
     * 产品的数据是官方过来的
     * @return false
     */
    public function officialData()
    {
        $pro = db('productinfo')->where('isdelete',0)->select();
        if(!isset($pro)) return false;
        $nowtime = time();
        $thisdatas = array();
        foreach ($pro as $k => $v) {
            //验证休市
            $isopen = ChickIsOpen($v['pid']);
            if($isopen){continue;}
            //腾讯证券
            if($v['procode'] == "btc" || $v['procode'] == "ltc"|| $v['procode'] == "eth" || $v['procode'] == "eos"){
                $minute = date('i',$nowtime);
                if($minute >= 0 && $minute < 15){ $minute = 0;}
                elseif($minute >= 15 && $minute < 30){ $minute = 15;}
                elseif($minute >= 30 && $minute < 45){ $minute = 30;}
                elseif($minute >= 45 && $minute < 60){ $minute = 45;}
                $new_date = strtotime(date('Y-m-d H',$nowtime).':'.$minute.':00');
                if($v['procode'] == 'btc'){
                    $url = ' ';
                }elseif($v['procode'] == 'ltc'){
                    $url = 'http://api.zb.plus/data/v1/ticker?market=ltc_usdt';
                }elseif($v['procode'] == 'eth'){
                    $url = 'http://api.zb.plus/data/v1/ticker?market=eth_usdt';
                }elseif($v['procode'] == 'eos'){
                    $url = 'http://api.zb.plus/data/v1/ticker?market=eos_usdt';
                }
                $getdata = $this->curlfun($url);
                $res = json_decode($getdata,1);
                $data_arr=$res['ticker'];
                if(!is_array($data_arr)) continue;
                $thisdata['Price'] = $this->fengkong($data_arr['sell'],$v);
                $thisdata['Open'] = $data_arr['buy'];
                $thisdata['Close'] = $data_arr['last'];
                $thisdata['High'] = $data_arr['high'];
                $thisdata['Low'] = $data_arr['low'];
                $thisdata['Diff'] = 0;
                $thisdata['DiffRate'] = 0;
                $thisdata['Name'] = $v['ptitle'];
            }elseif(in_array($v['procode'],array("sz399300"))){
                $url = "http://web.sqt.gtimg.cn/q=".$v['procode']."?r=0.".$nowtime*88;
                $getdata = $this->curlfun($url);
                $data_arr = explode('~',$getdata);
                $thisdata['Price'] = $data_arr[3];
                $thisdata['Open'] = $data_arr[4];
                $thisdata['Close'] = $data_arr[5];
                $thisdata['High'] = $data_arr[41];
                $thisdata['Low'] = $data_arr[42];
                $thisdata['Diff'] = 0;
                $thisdata['DiffRate'] = 0;
            }elseif(in_array($v['procode'],array(12,13,116))){  	//口袋贵金属
                $url = 'https://m.sojex.net/api.do?rtp=GetQuotesDetail&id='.$v['procode'];
                $html = $this->curlfun($url);
                $res = json_decode($html,1);
                $res = $res['data']['quotes'];
                $thisdata['Price'] = $res['buy'];
                $thisdata['Open'] = $res['open'];
                $thisdata['Close'] = $res['last_close'];
                $thisdata['High'] = $res['top'];
                $thisdata['Low'] = $res['low'];
                $thisdata['Diff'] = 0;
                $thisdata['DiffRate'] = 0;
            }elseif(in_array($v['procode'],array('llg','lls'))){
                $url = "https://www.91pme.com/marketdata/gethq?code=".$v['procode'];
                $html = $this->curlfun($url);
                $arr = json_decode($html,1);
                if(!isset($arr[0])) continue;
                $data_arr = $arr[0];
                $thisdata['Price'] = $this->fengkong($data_arr['buy'],$v);;
                $thisdata['Open'] = $data_arr['open'];
                $thisdata['Close'] = $data_arr['lastclose'];
                $thisdata['High'] = $data_arr['high'];
                $thisdata['Low'] = $data_arr['low'];
                $thisdata['Diff'] = 0;
                $thisdata['DiffRate'] = 0;
            }else{
                $url = "http://hq.sinajs.cn/rn=".$nowtime."list=".$v['procode'];
                $getdata = $this->curlfun($url);
                $data_arr = explode(',',$getdata);
                if(!is_array($data_arr) || count($data_arr) != 18) continue;
                $thisdata['Price'] = $data_arr[1];
                $thisdata['Open'] = $data_arr[5];
                $thisdata['Close'] = $data_arr[3];
                $thisdata['High'] = $data_arr[6];
                $thisdata['Low'] = $data_arr[7];
                $thisdata['Diff'] = $data_arr[12];
                $thisdata['DiffRate'] = $data_arr[4]/10000;
            }
            $thisdata['Name'] = $v['ptitle'];
            $thisdata['UpdateTime'] = $nowtime;
            $thisdata['pid'] = $v['pid'];
            $thisdatas[$v['pid']] = $thisdata;
            $ids = db('productdata')->where('pid',$v['pid'])->update($thisdata);
        }
        cache('nowdata',$thisdatas);
        return 1;
    }

    /**
     * 产品的数据是随机生成的
     * @return false
     */
    public function randData()
    {
        $pro = db('productinfo')->where('isdelete',0)->select();
        if(!isset($pro)) return false;
        $nowtime = time();
        $thisdatas = array();
        foreach ($pro as $k => $v) {
            //验证休市，休市就不自动跳
            $isDeal = db('productdata')->field('is_deal')->where('pid', $v['pid'])->find();
            if(empty($isDeal) || (isset($isDeal['is_deal']) && $isDeal['is_deal'] == 0)){continue;}
            $getData = $this->getVariation($v);
            if (!empty($getData)) {
                $thisdata['Price'] = $getData['Price'];
                $thisdata['Open'] = $getData['Open'];
                $thisdata['Close'] = $getData['Close'];
                $thisdata['High'] = $getData['High'];
                $thisdata['Low'] = $getData['Low'];
                $thisdata['Diff'] = 0;
                $thisdata['DiffRate'] = 0;
                $thisdata['Name'] = $v['ptitle'];
                $thisdata['UpdateTime'] = $nowtime;
                $thisdata['pid'] = $v['pid'];
                $thisdatas[$v['pid']] = $thisdata;
                db('productdata')->where('pid', $v['pid'])->update($thisdata);
            }
        }
        cache('nowdata',$thisdatas);
        return 1;
    }

    public function getVariation($value){
        $res = [];
        $product = db('productdata')->field('Price,High,Low')->where('pid',$value['pid'])->find();
        if(!empty($product)){
            $res['Open'] = $price1 = $price = $product['Price'];
            $num = strlen(intval($price));
            if($num == 1){
                $price1 *= 10000;
                $rand2 = rand(2, 30);
                $res['Close'] = ($price1 + $rand2)/10000;
            }elseif($num == 2){
                $price1 *= 1000;
                $rand2 = rand(5, 50);
                $res['Close'] = ($price1 + $rand2)/1000;
            }else{
                $price1 *= 100;
                $rand2 = rand(10, 100);
                $res['Close'] = ($price1 + $rand2)/100;
            }
            $today = date('Ymd');
            $High_key = $today.$value['procode'].'_'.$value['pid'].'_High';  //缓存key：当天+产品+pid
            $Low_key = $today.$value['procode'].'_'.$value['pid'].'_Low';
            if(!cache($High_key)){
                cache($High_key, $price);
                cache($Low_key, $price);
            }
            $high = floatval(cache($High_key));
            $low = floatval(cache($Low_key));
            if($price > $high){
                cache($High_key, $price);
            }
            if($price < $low){
                cache($Low_key, $price);
            }
            $res['Price'] = $this->fengkong($price, $value);
            $res['High'] = cache($High_key);
            $res['Low'] = cache($Low_key);
        }
        return $res;
    }

    /**
     * 官方数据风控
     * @author lukui  2017-06-27
     * @param  [type] $price [description]
     * @param  [type] $pro   [description]
     * @return [type]        [description]
     */
    public function fengkong($price,$pro)
    {
        $point_low = $pro['point_low'];
        $point_top = $pro['point_top'];
        $FloatLength = getFloatLength($point_low);
        $jishu_rand = pow(10,$FloatLength);
        $point_low = $point_low * $jishu_rand;
        $point_top = $point_top * $jishu_rand;
        $rand = rand($point_low,$point_top)/$jishu_rand;
        $_new_rand = rand(0,10);
        if($_new_rand % 2 == 0){
            $price = $price + $rand;
        }else{
            $price = $price - $rand;
        }
        return $price;
    }

    //curl获取数据
    public function curlfun($url, $params = array(), $method = 'GET')
    {
        $header = array();
        $opts = array(CURLOPT_TIMEOUT => 10, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_HTTPHEADER => $header);
        switch (strtoupper($method)) {
            case 'GET' :
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                $opts[CURLOPT_URL] = substr($opts[CURLOPT_URL],0,-1);
                break;
            case 'POST' :
                //判断是否传输文件
                $params = http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default :
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if($error){
            $data = null;
        }
        return $data;
    }


    /**
     * 订单更新
     * @throws order
     */
    function order()
    {
        $where['ostaus'] = 0;
        $where['isshow'] = 0;
        $order = db('order')->where($where)->select();
        if (!$order) return false;
        $time = time();
        foreach ($order as $key=>$val){
            if($time >= $val['selltime']){
                $price = db('productdata')->where('pid', $val['pid'])->value('Price');
                unsettledOrder($val, $price);
            }
        }
        return 1;
    }
}