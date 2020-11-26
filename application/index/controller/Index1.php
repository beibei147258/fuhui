<?php

namespace app\index\controller;

use think\Db;
use think\Cookie;

header("Access-Control-Allow-Origin:*");


class Index extends Base
{

    /**
     * 首页 行情列表
     * @return [type] [description]
     * @author lukui  2017-02-18
     */
    public function index()
    {
        if (!input('token')) {
            $this->redirect('index/login/login?token=' . $this->token);
        }
        //获取产品信息
        $pro = Db::name('productinfo')->alias('pi')->field('pi.pid,pi.ptitle,pd.Price,pd.UpdateTime,pd.Low,pd.High')
            ->join('__PRODUCTDATA__ pd', 'pd.pid=pi.pid')
            ->where('pi.isdelete', 0)->order('pi.proorder asc')->select();
        //dump(cookie('pid7'));
        $this->assign('pro', $pro);


        /*
        //晒单功能
        $order_pub = Db::name('productinfo')->alias('pi')->field('pi.pid,pi.ptitle,pd.otype,pd.oid,pd.buytime,pd.price')
                ->join('order_pub pd','pd.pid=pi.pid')
                ->where('pd.buytime > 0')->order('pd.buytime desc')->select();

        $id_arr = array();
        foreach($pro as $r){
            array_push($id_arr,$r["pid"]);
        }
        $pro_length = count($id_arr);

        $price_arr = array(100,100,100,100,200,200,200,500,500,500,1000,1000,5000,5000,10000,20000);
        $price_arr_length = count($price_arr);

        $type_arr = array('买涨','买跌');
        $type_arr_length = count($type_arr);

        for($i=0;$i<$pro_length;$i++){
            $rand_pid_index = rand(0,($pro_length - 1));
            $rand_price_index = rand(0,($price_arr_length - 1));
            $rand_type_index = rand(0,($type_arr_length - 1));

            $o_pub = array();
            $o_pub['buytime'] = time();
            $o_pub['pid'] = $id_arr[$rand_pid_index];
            $o_pub['price'] = $price_arr[$rand_price_index];
            $o_pub['otype'] = $type_arr[$rand_type_index];
            db('order_pub')->insert($o_pub);
        }

        foreach($order_pub as $k => $v){
            $order_pub[$k]['buytime'] = date("H:i:s",$v['buytime']);
        }
        $this->assign('order_pub',$order_pub);
        */


        return $this->fetch();
    }

    //网站公告
    public function ggkg()
    {
        $ggkg = Db::name('config')->where('name', 'ggkg')->find();
        $web_gonggao = Db::name('config')->where('name', 'web_gonggao')->find();
        return ['status' => $ggkg['value'], 'message' => $web_gonggao['value']];
    }

    //我的信用分
    public function myxyf()
    {
        $uid = $this->uid;
        $user = Db::name('userinfo')->where('uid', $uid)->find();
        //dump($user);
        $this->assign('user', $user);
        return $this->fetch();
    }

    public function ajax_order()
    {
        $pro_length = 50;
        $phone_pre_arr = array("139", "138", "137", "136", "135", "134", "159", "158", "157", "150", "151", "152", "187", "188", "130", "131", "132", "156", "155", "133", "153", "189");
        $phone_pre_length = count($phone_pre_arr);
        //$price_arr = array(100,200,300,400,500,600,700,800,500,500,1000,1000,5000,5000,10000,20000);
        //$price_arr_length = count($price_arr);
        $type_arr = array('买涨', '买跌');
        $type_arr_length = count($type_arr);
        $order_pub = array();
        for ($i = 0; $i < $pro_length; $i++) {
            //$rand_pid_index = rand(0,($pro_length - 1));
            $phone_pre_index = rand(0, ($phone_pre_length - 1));
            //$rand_price_index = rand(0,($price_arr_length - 1));
            //$rand_type_index = rand(0,($type_arr_length - 1));

            $o_pub = array();
            //$o_pub['buytime'] = time();
            //$o_pub['pid'] = $id_arr[$rand_pid_index];
            $o_pub['phone'] = $phone_pre_arr[$phone_pre_index] . "****" . rand(1000, 9999);


            //$o_pub['price'] = $price_arr[$rand_price_index];

            $o_pub['price'] = $this->getrd();

            /*
            else if(rand(1,100)>=80){
                $o_pub['price'] = 50 * rand(0,100);
            }
            */

            //$o_pub['otype'] = $type_arr[$rand_type_index];
            array_push($order_pub, $o_pub);
        }


        //foreach($order_pub as $k => $v){
        //$order_pub[$k]['buytime'] = date("H:i:s",$v['buytime']);
        //}
        echo json_encode($order_pub);
    }

    public function ajaxindexpro()
    {
        //获取产品信息
        $pro = Db::name('productinfo')->alias('pi')->field('pi.pid,pi.ptitle,pd.Price,pd.UpdateTime,pd.Low,pd.High')
            ->join('__PRODUCTDATA__ pd', 'pd.pid=pi.pid')
            ->where('pi.isdelete', 0)->order('pi.pid desc')->select();
        $newpro = array();
        foreach ($pro as $k => $v) {
            $newpro[$v['pid']] = $pro[$k];
            $newpro[$v['pid']]['UpdateTime'] = date('H:i:s', $v['UpdateTime']);


            // if(!isset($_COOKIE['pid'.$v['pid']])){
            //     cookie('pid'.$v['pid'],$v['Price']);
            //     continue;
            // }
            if ($v['Price'] < session('pid' . $v['pid'])) {  //跌了
                $newpro[$v['pid']]['isup'] = 0;
            } elseif ($v['Price'] > session('pid' . $v['pid'])) {  //涨了
                $newpro[$v['pid']]['isup'] = 1;
            } else {  //没跌没涨
                $newpro[$v['pid']]['isup'] = 2;
            }

            session('pid' . $v['pid'], $v['Price']);

        }

        return base64_encode(json_encode($newpro));
    }

    public function getchart()
    {

        $data['hangqing'] = '商品行情';
        $data['jiaoyijilu'] = '交易记录';
        $data['shangpinmingcheng'] = '商品名称';
        $data['xianjia'] = '现价';
        $data['zuidi'] = '最低';
        $data['zuigao'] = '最高';
        $data['xianjia'] = '现价';
        $data['xianjia'] = '现价';


        $res = base64_encode(json_encode($data));
        return $res;
    }

    public function getrd()
    {
        $rdarr = array(88, 90, 92, 93, 176, 180, 184, 186, 264, 270, 276,
            279, 352, 440, 450, 460, 465, 880, 900, 920, 930, 1760, 1800, 1840,
            4400, 4500, 4600, 4650, 8800, 9000, 9200, 9300, 17600, 18000, 18400, 18600);

        return $rdarr[array_rand($rdarr)];
    }

    /**
     * 获取最新的动态数据
     */
    public function ajaxdata()
    {
        $product = cache('nowdata');
        if (!isset($product) || empty($product)) {
            $product = db('productdata')->field("pid,Name,Price,isdelete")->where(array('isdelete' => 0))->select();
        }
        foreach ($product as $k => $val) {
            //   $rd = rand(-3,3);
            //  $product[$k]['price'] = $val['price'] +$rd*0.01*$val['price'];
            $lastprice = session('price' . $val['pid']);
            $product[$k]['is_rise'] = ($lastprice >= $val['Price']) ? 1 : 2;
            session('price' . $val['pid'], $product[$k]['Price']);
        }
        return json_encode($product);
    }

    /*public  function ajaxdata() {
        $product = cache('nowdata');

        foreach( $product as $k=>$val) {
            //   $rd = rand(-3,3);
            //  $product[$k]['price'] = $val['price'] +$rd*0.01*$val['price'];
            $lastprice= session('price'.$val['pid']);
            $product[$k]['is_rise']=($lastprice>=$val['Price'])?1:2;
            session('Price'.$val['pid'],$product[$k]['Price']);
        }
        return  json_encode($product);
    }*/

    public function home()
    {

//        if (input('post.')) {
//            $data = input('post.'); //前端对应的数据
//
//            $result = db('userinfo')
//                ->where('username', $data['username'])->whereOr('nickname', $data['username'])->whereOr('utel', $data['username'])
//                ->field("uid,upwd,username,utel,utime,otype,ustatus")->find();
//            $_SESSION['uid'] = $result['uid'];
//            $_SESSION['sessionkey'] = rand(10000, 99999);
//            $t_data['sessionkey'] = $_SESSION['sessionkey'];
//            $t_data['logintime'] = time();
//            $t_data['uid'] = $result['uid'];
//            db('userinfo')->update($t_data);
//        }

        if (!isset($_SESSION['uid'])) {
            $this->redirect('/index/index/home?token=' . $this->token);
        }
        $product = db('productdata')->where(array('isdelete' => 0))->select();
        // p($product);

        $productInfo = [
            ['img' => "http://ghswxx8.com/Public/Home/productImg/GU.png", 'ptitle' => '欧洲指数',
                'procode' => 'btc', 'point' => 5219, 'is_deal' => 1, 'is_rise' => '1'],
            ['img' => "http://ghswxx8.com/Public/Home/productImg/GU.png", 'ptitle' => '欧洲指数',
                'procode' => 'btc', 'point' => 5219, 'is_deal' => 1, 'is_rise' => '1'],
            ['img' => "http://ghswxx8.com/Public/Home/productImg/GU.png", 'ptitle' => '欧洲指数',
                'procode' => 'btc', 'point' => 5219, 'is_deal' => 1, 'is_rise' => '1'],
            ['img' => "http://ghswxx8.com/Public/Home/productImg/GU.png", 'ptitle' => '欧洲指数',
                'procode' => 'btc', 'point' => 5219, 'is_deal' => 1, 'is_rise' => '1'],
        ];
        $bannerImg = [
            '/static/index/img/a1.png',
            '/static/index/img/a2.png',
            '/static/index/img/a3.png',
            '/static/index/img/a4.png',
        ];
        if(!empty($this->conf['bannerimg2'])){
            $bannerImg = explode(',', $this->conf['bannerimg2']);
        }
        $this->assign('bannerImg', $bannerImg);
        $this->assign('pro', $product);
        return $this->fetch();

    }

    public function home1()
    {
        if (input('post.')) {
            $data = input('post.'); //前端对应的数据

            $result = db('userinfo')
                ->where('username', $data['username'])->whereOr('nickname', $data['username'])->whereOr('utel', $data['username'])
                ->field("uid,upwd,username,utel,utime,otype,ustatus")->find();
            $_SESSION['uid'] = $result['uid'];
            $_SESSION['sessionkey'] = rand(10000, 99999);
            $t_data['sessionkey'] = $_SESSION['sessionkey'];
            $t_data['logintime'] = time();
            $t_data['uid'] = $result['uid'];
            db('userinfo')->update($t_data);
        }
        $product = db('productdata')->where(array('isdelete' => 0))->select();
        // p($product);

        $productInfo = [
            ['img' => "http://ghswxx8.com/Public/Home/productImg/GU.png", 'ptitle' => '欧洲指数',
                'procode' => 'btc', 'point' => 5219, 'is_deal' => 1, 'is_rise' => '1'],
            ['img' => "http://ghswxx8.com/Public/Home/productImg/GU.png", 'ptitle' => '欧洲指数',
                'procode' => 'btc', 'point' => 5219, 'is_deal' => 1, 'is_rise' => '1'],
            ['img' => "http://ghswxx8.com/Public/Home/productImg/GU.png", 'ptitle' => '欧洲指数',
                'procode' => 'btc', 'point' => 5219, 'is_deal' => 1, 'is_rise' => '1'],
            ['img' => "http://ghswxx8.com/Public/Home/productImg/GU.png", 'ptitle' => '欧洲指数',
                'procode' => 'btc', 'point' => 5219, 'is_deal' => 1, 'is_rise' => '1'],
        ];
        $this->assign('pro', $product);
        return $this->fetch();
    }

    /**
     * 用户的系统中心
     * @return mixed|string
     */
    public function mine()
    {
        $userInfo = $this->user;
        $mailcount = Db::name('mail')->where('uid', $userInfo['uid'])->where('state', '0')->count();//0未读1已读
        $this->assign('mailcount', $mailcount);
        $this->assign('userInfo', $userInfo);
        return $this->fetch('mine');
    }

    public function member()
    {
        $userInfo = $this->user;
        $mailcount = Db::name('mail')->where('uid', $userInfo['uid'])->where('state', '0')->count();//0未读1已读
        $this->assign('mailcount', $mailcount);
        $this->assign('userInfo', $userInfo);
        return $this->fetch('mine');
    }

    //站内信息列表
    public function mail()
    {
        $userInfo = $this->user;
        $mail = Db::name('mail')->where('uid', $userInfo['uid'])->select();//0未读1已读
        $this->assign('mail', $mail);
        return $this->fetch('mail');
    }

    public function znxx()
    {
        $id = input('param.id');
        $mail = Db::name('mail')->where('id', $id)->find();
        $data['state'] = 1;
        $data['update_time'] = time();
        Db::name('mail')->where('id', $id)->update($data);
        $this->assign('mail', $mail);
        return $this->fetch('znxx');
    }

    public function minen()
    {

        return $this->fetch();
    }

    public function pay()
    {
        $conf = $this->conf;
        $arr = explode('|', $conf['reg_push']);
        $this->assign('reg_push', $arr);
        $this->assign('pay_bl', $conf['paz_bl']);

        $map['name'] = 'skyh';
        $skyh = \db('config')->where($map)->find();

        $map1['name'] = 'khyh';
        $khyh = \db('config')->where($map1)->find();

        $map2['name'] = 'skxm';
        $skxm = \db('config')->where($map2)->find();

        $map2['name'] = 'skzh';
        $skzh = \db('config')->where($map2)->find();

        $this->assign('skyh', $skyh);
        $this->assign('khyh', $khyh);
        $this->assign('skxm', $skxm);
        $this->assign('skzh', $skzh);

        return $this->fetch();
    }

    public function login()
    {
        if ($_POST) {
            // p($_POST);
            $res = db('userinfo')->where(array('username' => $_POST['username']))->find();

            return $this->fetch('home');
        } else {
            return $this->fetch();
        }
    }

    public function am()
    {
        return $this->fetch();
    }

    public function hold()
    {
        return $this->fetch();
    }

    public function winvoucher()
    {
        return $this->fetch();
    }


    public function inquiries()
    {
        $uid = $_SESSION['uid'] ? $_SESSION['uid'] : 72;
        $orders = db('order')->field('uid,ptitle,is_win,ploss,ostyle,fee,ploss')->where(['uid' => $uid])->order('oid desc')->select();
        //p($orders);
        foreach ($orders as $k => $val) {
            $orders[$k]['name'] = $val['ptitle'];
            $orders[$k]['fx'] = ($val['ostyle'] == 0) ? '买涨' : '买跌';
            $orders[$k]['yk'] = $val['ploss'];
            $orders[$k]['money'] = $val['fee'];
        }
        // p($orders);
//        $orders = [
//            ['name'=>'欧洲指数','money'=>'100.00','fx'=>'买涨','yk'=>'+80'],
//            ['name'=>'欧元英镑','money'=>'200.00','fx'=>'买涨','yk'=>'-160'],
//            ['name'=>'欧洲指数','money'=>'150.00','fx'=>'买涨','yk'=>'-100'],
//
//
//        ];
        $this->assign('orders', $orders);
        return $this->fetch();
    }

    public function accountrecord()
    {
        $uid = $_SESSION['uid'] ? $_SESSION['uid'] : 72;
        $acountrf = db('balance')->where(['uid' => $uid])->order('bpid desc')->select();

        foreach ($acountrf as $key => $val) {
            $dealhis[$key]['utime'] = date('Y-m-d H:i:s', $val['bptime']);

            $dealhis[$key]['typedesc'] = ($val['bptype'] == 1) ? '充值' : (($val['bptype'] == 2) ? "自动充值" : "提现");
            $dealhis[$key]['money'] = $val['bpprice'];
            //$dealhis[$key]['is_verify']= ($val['isverified']==1)?'审核通过':'待审核';
            if ($val['isverified'] == 1) {
                $dealhis[$key]['is_verify'] = '审核通过';
            } elseif ($val['isverified'] == 2) {
                $dealhis[$key]['is_verify'] = '拒绝';
            } else {
                $dealhis[$key]['is_verify'] = '待审核';
            }
            $dealhis[$key]['remarks'] = $val['remarks'];
        }
        //p($dealhis);
        //  $dealhis = [['money'=>100],['money'=>120],['money'=>150]];
        if (!$acountrf) {
            $dealhis = 1;
        }
        $this->assign('dealhis', $dealhis);
        return $this->fetch();
    }

    public function activitycenter()
    {
        return $this->fetch();
    }

    public function ruleintroduce()
    {
        return $this->fetch();
    }

    public function historynotice()
    {
        return $this->fetch();
    }

    public function gywm()
    {//关于我们
        return $this->fetch();
    }

    public function bankcard()
    {
        $uid = $_SESSION['uid'] ? $_SESSION['uid'] : 72;
        $bankcards = db('bankcard')->field('*')->where(['uid' => $uid])->find();
        $count = count($bankcards);
        $banks = Db::name('banks')->where(['id' => $bankcards['bankno']])->find();
        $bankcards['province'] = $banks['bank_nm'];
        $s = sizeof($bankcards);
        $this->assign('count', $count);
        $this->assign('bankcards', $bankcards);
        $this->assign('s', $s);

        //  p($bankcards);
        return $this->fetch();
    }

    public function applicant()
    {
        return $this->fetch();
    }

    public function moditypwd()
    {
        $uid = $_SESSION['uid'];
        $userinfo = db('userinfo')->where(['uid' => $uid])->find();
        $arr = array();
        if (input('post.')) {
            $data = input('post.');
            if ($data['oldpwd'] != $userinfo['upwd']) {
                return json_encode(2);
            }
            if ($data['newpwd']) $arr['upwd'] = (trim($data['newpwd']));
            if ($data['txpwd']) $arr['txpwd'] = (trim($data['txpwd']));
            $r = db('userinfo')->where(['uid' => $uid])->update($arr);
            return json_encode(1);
        }
        return $this->fetch();
    }

    public function respwd()
    {
        $uid = $_SESSION['uid'] ? $_SESSION['uid'] : 72;
        $data = input('post.');
        $pwd = ($data['oldpwd']);
        $name = $data['name'];

        $newpwd = $data['newpwd'];
        $newpwd2 = $data['newpwd2'];
        $res = db('userinfo')->where(['uid' => $uid, 'upwd' => $pwd])->select();
        // dump($data);
        $row = array('upwd' => ($newpwd), 'username' => $name);
        if ($res && ($newpwd == $newpwd2)) {
            $ok = db('userinfo')->where(['uid' => $uid, 'upwd' => $pwd])->update($row);

            if ($ok) {
                return json_encode(1);
            } else {
                return json_encode(0);
            }
        } else {
            return json_encode(0);
        }

    }


    public function palyStep()
    {
        return $this->fetch();
    }

    public function sdpay()
    {
        $data = input('get.');
        $conf = $this->conf;
        $this->assign('money', $data['money']);
        if ($data['type'] == 2) {
            $this->assign('type', 1);
            $this->assign('typeName', '支付宝');
            $this->assign('ewm', $conf['alipay_code']);
        } else if ($data['type'] == 5 || $data['type'] == 3005) {
            $this->assign('type', 3);
            $this->assign('typeName', '微信');
            $this->assign('ewm', $conf['weixin_code']);
        }


        if (!$data) {
            $this->error('参数错误！');
        }

        if (!$data['money'] || !$data['type']) {
            return WPreturn('参数错误！', -1);
        }


        $uid = $this->uid;
        $user = $this->user;
        //dump($user);die;
        $nowtime = time();

        //插入充值数据
        $reamrks = "";
        if ($data['type'] == 1) {
            $reamrks = "微信支付";
        } else if ($data['type'] == 2) {
            $reamrks = "支付宝支付";
        } else {
            $reamrks = "银行卡转账";
        }
        $data1['bptype'] = 3;
        $data1['bptime'] = $nowtime;
        $data1['bpprice'] = $data['money'];
        $data1['remarks'] = $reamrks;
        $data1['uid'] = $uid;
        $data1['isverified'] = 0;
        $data1['btime'] = $nowtime;
        $data1['reg_par'] = 0;
        $data1['balance_sn'] = $uid . $nowtime . rand(111111, 999999);
        $data1['pay_type'] = 'ewmcode';
        $data1['bpbalance'] = $user['usermoney'];
        //$data1['tjname'] = $data['name'];

        $ids = db('balance')->insertGetId($data1);
        if (!$ids) {
            return WPreturn('网络异常！', -1);
        }

        //$this->success('添加成功','index/accountrecord');
        return redirect('index/accountrecord');
    }

    public function balanceList()
    {
        $uid = $this->uid;
        $b = db('balance')->where('uid', $uid)->order('bptime', 'desc')->select();

        return json_encode($b, JSON_UNESCAPED_UNICODE);
    }

    public function userInfo1()
    {
        $uid = $this->uid;
        $u = db('userinfo')->where('uid', $uid)->find();
        return json_encode($u, JSON_UNESCAPED_UNICODE);
    }


    public function loginout()
    {
        unset($_SESSION['uid']);
        $this->redirect('index/login/login');
    }

    /**
     * 用户的提现功能
     * @return mixed|string
     */
    public function withdraw()
    {
        $uid = $this->uid;
        if (input('post.')) {
            $data = input('post.');
            if ($data) {
                if(isset($data['accntno']) && !empty($data['accntno'])){
                    $params = [];
                    foreach ($data as $key=>$val){
                        if($key != 'price' && $key != 'txpwd'){
                            $params[$key] = $val;
                        }
                    }
                    $this->update_bank($params);
                    $bankcard = $data['accntno'];
                }else{
                    $banks = Db::name('bankcard')->field('accntno')->where('uid', $uid)->find();
                    $bankcard = $banks["accntno"];
                }
                if (!$data['price']) {
                    return WPreturn('请输入提现金额', 4001);
                }
                if (empty($data['txpwd'])) {
                    return WPreturn('请输入提现密码', 4001);
                }
                //验证申请金额
                $user = $this->user;

                if ($user['txpwd'] != $data['txpwd'] + '') {
                    return WPreturn('提现密码错误', 4001);
                }
                if ($user['ustatus'] != 0) {
                    return WPreturn('账户异常，临时冻结，请咨询在线客服', 4001);
                }
                $conf = $this->conf;

                if ($conf['is_cash'] != 1) {
                    return WPreturn('抱歉！暂时无法出金', 4001);
                }
                if ($conf['cash_min'] > $data['price']) {
                    return WPreturn('单笔最低提现金额为：' . $conf['cash_min'], 4001);
                }
                if ($conf['cash_max'] < $data['price']) {
                    return WPreturn('单笔最高提现金额为：' . $conf['cash_max'], 4001);
                }

                $_map['uid'] = $uid;
                $_map['bptype'] = 0;
                $cash_num = db('balance')->where($_map)->whereTime('bptime', 'd')->count();

                if ($cash_num + 1 > $conf['day_cash']) {
                    return WPreturn('当天最多提现' . $conf['day_cash'] . '次', 4001);
                }
                $cash_day_max = db('balance')->where($_map)->whereTime('bptime', 'd')->sum('bpprice');
                if ($conf['cash_day_max'] < $cash_day_max + $data['price']) {
                    return WPreturn('当日累计最高提现金额为：' . $conf['cash_day_max'], 4001);
                }


                $statrdate = Db::name("config")->where("name='role_ks'")->select();
                $txstatrdate = $statrdate[0]['value'] ? $statrdate[0]['value'] : 9;


                $enddate = Db::name("config")->where("name='role_js'")->select();
                $txenddate = $enddate[0]['value'] ? $enddate[0]['value'] : 17;


                if (date('H') < intval($txstatrdate) || date('H') > intval($txenddate)) {
                    return WPreturn('出金时间为' . $txstatrdate . '-' . $txenddate . '点', -1);
                }

                //代理商的话判断金额是否够
                if ($this->user['otype'] == 101) {
                    if (($this->user['usermoney'] - $data['price']) < $this->user['minprice']) {
                        return WPreturn('您的保证金是' . $this->user['minprice'] . '元，提现后余额不得少于保证金。', -1);
                    }
                }

                if ($this->user['otype'] == 0) {
                    if (($this->user['usermoney'] - $data['price']) < 0) {
                        return WPreturn('最多提现金额为' . $this->user['usermoney'] . '元', -1);
                    }
                }

                if (($this->user['usermoney'] - $data['price']) < 0) {
                    return WPreturn('最多提现金额为' . $this->user['usermoney'] . '元');
                }


                //签约信息
                //  $mybank = db('bankcard')->where('uid',$uid)->find();

                //提现申请
                $newdata['bpprice'] = $data['price'];
                $newdata['bptime'] = time();
                $newdata['bptype'] = 0;
                $newdata['remarks'] = '会员提现';
                $newdata['uid'] = $uid;
                $newdata['isverified'] = 0;
                $newdata['bpbalance'] = 0;
                $newdata['bankid'] = input('bankid');   // $data['bankcardno'];
                $newdata['bankcard'] = $bankcard;
                $newdata['btime'] = time();
                $newdata['reg_par'] = $conf['reg_par'];
                $bpid = Db::name('balance')->insertGetId($newdata);
                if ($bpid) {
                    //插入申请成功后,扣除金额
                    $editmoney = Db::name('userinfo')->where('uid', $uid)->setDec('usermoney', $data['price']);
                    if ($editmoney) {
                        //插入此刻的余额。
                        $usermoney = Db::name('userinfo')->where('uid', $uid)->value('usermoney');
                        Db::name('balance')->where('bpid', $bpid)->update(array('bpbalance' => $usermoney));
                        //资金日志
                        //set_price_log($uid,2,$data['price'],'提现','提现申请',$bpid,$usermoney);

                        return WPreturn('提现申请提交成功！', 1);
                    } else {
                        //扣除金额失败，删除提现记录
                        Db::name('balance')->where('bpid', $bpid)->delete();
                        return WPreturn('提现失败！');
                    }

                } else {
                    return WPreturn('提现失败！');
                }

            } else {
                return WPreturn('暂不支付此提现类型！');
            }
        } else {
            $bankinfo = db('bankcard')->where(['uid' => $uid])->order('id desc')->find();
            $count = count($bankinfo);
            $banks = Db::name('banks')->where(['id' => $bankinfo['bankno']])->find();
            $bankinfo['province'] = $banks['bank_nm'];
            $this->assign('bankinfo', $bankinfo);
            $this->assign('count', $count);
            if ($count == 0 && $this->conf['keep_card'] == 1) {
                return $this->fetch('bankcard');
            }
            return $this->fetch();
        }
    }


    public function tx()
    {
        header("Access-Control-Allow-Origin:*");
        $map = "null";
        $pz = Db::name('balance')->where('isverified', $map)->count();
        return $pz;
    }

    public function xs()
    {
        // $ids = db('order')->->update($data);
        $rq = date('Y-m-d');

        $map = "未查看";

        $xs = Db::name('order')->where('xs', $map)->where('buytime', '> time', $rq)->count();
        return $xs;
// var_dump($xs);
    }

    public function add_bank()
    {
        $uid = $_SESSION['uid'] ? $_SESSION['uid'] : 72;
        if (input('post.')) {
            $post = input('post.');
            $count = Db::name('bankcard')->where('uid', $post['uid'])->count();
            if ($count > 0) {
                $ids = db('bankcard')->where('uid', $post['uid'])->update($post);
            } else {
                $ids = db('bankcard')->insert($post);
            }
            $this->success('添加成功，正在跳转~~', 'index/bankcard');
        }
        $this->assign('uid', $uid);
        return $this->fetch();
    }

    public function update_bank($params = [])
    {
        $uid = $_SESSION['uid'] ? $_SESSION['uid'] : 72;
        $bank = Db::name('bankcard')->where('uid', $uid)->select();
        if(!empty($bank)){
            $type = !empty($params) ? 1 : 2;
            $data = !empty($params) ? $params : input('post.');
            if($data){
                db('bankcard')->where('uid', $uid)->update($data);
                if($type == 2){
                    $this->success('修改成功，正在跳转~~', 'index/bankcard');
                }
            }
            $this->assign('uid', $uid);
            $this->assign('bank', $bank[0]);
        }
        return $this->fetch();
    }

    public function getarea()
    {
        $id = input('id');
        if (!$id) {
            return false;
        }
        $list = db('area')->where('pid', $id)->select();
        $data = '<option value="">请选择</option>';
        foreach ($list as $k => $v) {
            $data .= '<option value="' . $v['id'] . '">' . $v['name'] . '</option>';
        }
        echo $data;
    }

}
