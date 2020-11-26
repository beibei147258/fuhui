<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Log;

class User extends Base
{
    /**
     * 用户列表
     * @author lukui  2017-02-16
     * @return [type] [description]
     */
    public function userlist()
    {
        $pagenum = cache('page');
        $getdata = $where = array();
        $data = input('param.');
        //用户名称、id、手机、昵称
        if(isset($data['username']) && !empty($data['username'])){
            $username = trim($data['username']);
            $where['username|uid|utel|nickname'] = array('like','%'.$username.'%');
            $getdata['username'] = $username;
        }

        if(isset($data['today']) && $data['today'] == 1){
            $getdata['starttime'] = strtotime(date("Y").'-'.date("m").'-'.date("d").' 00:00:00');
            $getdata['endtime'] = strtotime(date("Y").'-'.date("m").'-'.date("d").' 24:00:00');
            $where['utime'] = array('between time',array($getdata['starttime'],$getdata['endtime']));

        }
        $oid = input('oid');
        if($oid){
            $where['oid'] = $oid;
            $getdata['oid'] = $oid;
        }

        if(isset($data['uid']) && !empty($data['uid'])){
            $where['uid'] =$data['uid'];
            $getdata['uid'] =$data['uid'];
        }

        //权限检测
        if($this->otype != 3){

            $uids = myuids($this->uid);
            if(!empty($uids)){
                $where['uid'] = array('IN',$uids);
            }else{
                $where['uid'] = $this->uid;
            }
        }

        if(isset($data['otype']) && $data['otype'] != '' && in_array($data['otype'],array(0,101))){
            $where['otype'] = $data['otype'];
            $getdata['otype'] = $data['otype'];
        }else{
            $where['otype'] = array('IN',array(0,101));
        }


        $t = time() - 60*5;
        $c = db('inline')->where('updatime','gt',$t)->select();
        $zx = array();
        $y = 0;
        foreach ($c as $key => $value) {
            //$zx[$y] = $value['uid'];
            //$y ++;
            array_push($zx,$value['uid']);
        }
        if (sizeof($zx) > 0) {
            $up_zx['zxstatus'] = 1;
            Db::name('userinfo')->where('uid','in',$zx)->update($up_zx);
            //$upsql = Db::getLastSql();
            //dump($upsql);
            //Db::name('userinfo')->where('uid','in',$zx)->update(['zxstatus' => 1]);
            //\db('userinfo')->where('uid','in',$zx)->update(['zxstatus' => 1]);
            $up_bzx['zxstatus'] = 0;
            Db::name('userinfo')->where('uid','not in',$zx)->update($up_bzx);
            //Db::table('wp_userinfo')->where('uid','not in',$zx)->update(['zxstatus' => 0]);
            //\db('userinfo')->where('uid','not in',$zx)->update(['zxstatus' => 0]);
        }

        //dump($where);
        //exit;
        $userinfo = Db::name('userinfo')->where($where)->order('zxstatus desc , utime desc')->paginate($pagenum,false,['query'=> $getdata]);
        // p($userinfo);
        $this->assign('userinfo',$userinfo);
        $this->assign('getdata',$getdata);
        return $this->fetch();
    }



    //测试
    public function test(){
        $userinfo = Db::name('userinfo')->order('uid desc')->paginate(20);
        $info=$userinfo->all(); //转换成数组

        foreach($info as $key =>$val){
            //计算二级代理金额
            $two_oid=$val['uid'];
            $two_money=Db::query("select sum(usermoney) total_money from wp_userinfo where oid=$two_oid ");
            $two_uid=Db::query("select uid ,otype from wp_userinfo where oid=$two_oid ");

//                    $info->items()[$key]['total_money']=$val['usermoney']+$two_money[0]['total_money'];
            $info[$key]['total_money']=9999;


            //计算三级代理金额
            foreach($two_uid as $k =>$v){
                if($v['otype']==101){  //如果是代理商身份
                    $three_oid=$v['uid'];
                    $total_money=Db::query("select sum(usermoney) as total_money  from wp_userinfo where oid=$three_oid ");
                    $info[$key]['total_money']=$info[$key]['total_money']+$total_money[0]['total_money'];
                }
            }
        }
//            print_r($info);
//            exit;
        $this->assign('userinfo',$info);
        return $this->fetch();
    }






    /**
     * 添加用户
     * @author lukui  2017-02-16
     * @return [type] [description]
     */
    public function useradd()
    {
        if(input('post.')){
            $data = input('post.');
            $data['utime'] = time();
            $data['comname'] = trim($data['upwd']);
            $data['upwd2'] = $data['upwd'] = md5(trim($data['upwd']));
            $data['oid'] = $_SESSION['userid'];
            $data['managername'] = db('userinfo')->where('uid',$data['oid'])->value('username');
            $data['username'] = $data['utime'];

            $data['zxstatus'] = 0;

            $issetutl = db('userinfo')->where('utel',$data['utel'])->find();
            if($issetutl){
                return WPreturn('该手机号已存在!',-1);
            }

            //去除空字符串，无用字符串
            $data = array_filter($data);
            //插入数据
            $ids = Db::name('userinfo')->insertGetId($data);

            $newdata['uid'] = $ids;
            $newdata['username'] = 10000000+$ids;

            $newids = Db::name('userinfo')->update($newdata);

            if ($newids) {
                return WPreturn('添加用户成功!',1);
            }else{
                return WPreturn('添加用户失败,请重试!',-1);
            }
        }else{
            $this->assign('isedit',0);
            return $this->fetch();
        }

    }

    /**
     * 编辑用户
     * @author lukui  2017-02-16
     * @return [type] [description]
     */
    public function useredit()
    {

        if(input('post.')){
            $data = input('post.');
            if(!isset($data['uid']) || empty($data['uid'])){
                return WPreturn('参数错误,缺少用户id!',-1);
            }
            $where['uid'] = $data['uid'];
            //修改密码
            if(true){
                //验证用户密码
                $utime = Db::name('userinfo')->where($where)->value('utime');

                /*if(!isset($data['upwd']) || empty($data['upwd'])){
                    return WPreturn('如需修改密码请输入新密码!',-1);
                }*/
                if(isset($data['upwd']) && isset($data['upwd2']) && $data['upwd'] != $data['upwd2']){
                    return WPreturn('两次输入密码不同!',-1);
                }
                $data['comname'] = trim($data['upwd']);
                $data['upwd2'] = $data['upwd'] = md5(trim($data['upwd']));
            }
            //去除空字符串和多余字符串
            $data = array_filter($data);
            if(!isset($data['ustatus'])){
                $data['ustatus'] = 0;
            }

            //判断是否修改了金额，如修改金额需插入balance记录
            if(!isset($data['usermoney'])){
                $data['usermoney'] = 0;
            }
            if(!isset($data['ordusermoney'])){
                $data['ordusermoney'] = 0;
            }

            if($data['usermoney'] != $data['ordusermoney']){
                $b_data['bptype'] = 2;
                $b_data['bptime'] = $b_data['cltime'] = time();
                $b_data['bpprice'] = $data['usermoney'] - $data['ordusermoney'] ;
                //	$b_data['remarks'] = '后台管理员id'.$_SESSION['userid'].'编辑客户信息改动金额';
                $b_data['remarks'] = '系统审核通过充值';
                $b_data['uid'] = $data['uid'];
                $b_data['isverified'] = 1;
                $b_data['bpbalance'] = $data['usermoney'];
                $addbal = Db::name('balance')->insertGetId($b_data);
                if(!$addbal){
                    return WPreturn('增加金额失败，请重试!',-1);
                }
            }
            unset($data['ordusermoney']);
            unset($data['uid']);
            $editid = Db::name('userinfo')->where($where)->update($data);
            if ($editid) {
                return WPreturn('修改用户成功!',1);
            }else{
                return WPreturn('如不修改，请返回用户列表!',-1);
            }
        }else{

            $uid = input('param.uid');
            $where['uid'] = $uid;
            $sfz = Db::name('bankcard')->where('uid',$uid)->find();

            $sfzs = $sfz['sfz'];
            $userinfo = Db::name('userinfo')->where($where)->find();

            unset($userinfo['otype']);
            //获取用户所属信息
            $oidinfo = GetUserOidInfo($uid,'username,oid');
            if(!cache('memberLevel')){
                $mLevel = [
                    ['name' => '普通会员', 'src'=>''],
                    ['name' => '贵宾会员', 'src'=>''],
                    ['name' => '黄金VIP会员', 'src'=>''],
                    ['name' => '铂金VIP会员', 'src'=>''],
                    ['name' => '爵金VIP会员', 'src'=>''],
                    ['name' => '钻石VIP会员', 'src'=>''],
                    ['name' => '伯爵VIP会员', 'src'=>''],
                    ['name' => '至尊VIP会员', 'src'=>''],
                ];
                cache('memberLevel', $mLevel);
            }
            $memberLevel = cache('memberLevel');
            $this->assign('uid', $uid);
            $this->assign('memberLevel', $memberLevel);
            $this->assign('userinfo', $userinfo);
            $this->assign('isedit',1);
            $this->assign($oidinfo);
            $this->assign('sfzs',$sfzs);
            return $this->fetch('useradd');
        }

    }

    public function cardinfo() {
        $uid = input('param.uid');
        if(!$uid){
            $this->error('参数错误！');
        }
        //gb
        $bank = db('bankcard')->alias('bc')->field('bc.*,bs.bank_nm')
            ->join('__BANKS__ bs','bs.id=bc.bankno')
            ->where('uid',$uid)
            ->find();

        $banks = db('banks')->select();
        $user = db('userinfo')->where('uid',$uid)->find();
        if(!cache('province')){
            $province = db('area')->where(array('pid' => 0))->select();
            cache('province',$province);
        }
        $this->assign('uid',$uid);
        $this->assign('banks',$banks);
        $this->assign('province', cache('province'));
        $this->assign('user',$user);
        $this->assign('bank',$bank);
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

    public function cardedit() {
        if(input('post.')){
            $data = input('post.');
            $userinfo = db('bankcard');
            $user = $userinfo->where('uid',$data['uid'])->find();
            if($user){
                Db::name('userinfo')->where('uid',$data['uid'])->update(['txpwd' => $data['txpwd']]);
                unset($data['txpwd']);
                $editid = Db::name('bankcard')->where('uid',$data['uid'])->update($data);
            }else{

                Db::name('userinfo')->where('uid',$data['uid'])->update(['txpwd' => $data['txpwd']]);
            }
            $this->success("修改成功",'userlist');
        }
        //return json_encode(1);
    }

    /**
     * 充值和提现
     * @author lukui  2017-02-16
     * @return [type] [description]
     */
    public function userprice(){

        $pagenum = cache('page');
        $getdata = $where = array();
        $selectType = 0;
        $data = input('');
        $payType = $this->conf['pay_profit'] == 1 ? ['未处理', '用户充值', '手动充值', '配置收益', '自动收益'] : ['未处理', '用户充值', '手动充值'];
        $where['bptype'] = array('IN',array(1,2,3,4,7));
        //类型
        if(isset($data['bptype']) && $data['bptype'] != ''){
            $selectType = $data['bptype'];
            if($data['bptype'] == 1){
                $where['bptype'] = 3;
            }elseif($data['bptype'] == 2){
                $where['bptype'] = 1;
            }elseif($data['bptype'] == 3){
                $where['bptype'] = 2;
            }elseif($data['bptype'] == 4){
                $where['collocation'] = 1;
            }elseif($data['bptype'] == 5){
                $where['bptype'] = 7;
            }
            $getdata['bptype'] = $data['bptype'];
        }

        //用户名称、id、手机、昵称
        if(isset($data['username']) && !empty($data['username'])){
            $data['username'] = trim($data['username']);
            if($data['stype'] == 1){
                $where['username|u.uid|utel|nickname'] = array('like','%'.$data['username'].'%');
            }
            if($data['stype'] == 2){
                $puid = db('userinfo')->where(array('username'=>$data['username']))->whereOr('utel',$data['username'])->value('uid');
                if(!$puid) $puid = 0;
                $where['u.oid'] = $puid;
            }


            $getdata['username'] = $data['username'];
            $getdata['stype'] = $data['stype'];
        }

        //时间搜索
        if(isset($data['starttime']) && !empty($data['starttime'])){
            if(!isset($data['endtime']) || empty($data['endtime'])){
                $data['endtime'] = date('Y-m-d H:i:s',time());
            }
            $where['bptime'] = array('between time',array($data['starttime'],$data['endtime']));
            $getdata['starttime'] = $data['starttime'];
            $getdata['endtime'] = $data['endtime'];
        }

        //权限检测
        if($this->otype != 3){

            $uids = myuids($this->uid);
            if(!empty($uids)){
                $where['u.uid'] = array('IN',$uids);
            }
        }
        $balData = Db::name('balance')->alias('b')->field('b.*,u.utel,u.username,u.nickname,u.oid')
            ->join('__USERINFO__ u','u.uid=b.uid')
            ->where($where)->order('bpid desc')->paginate($pagenum,false,['query'=> $getdata]);
        $all_bpprice = Db::name('price_log')->alias('p')->field('p.*,u.username,u.nickname,u.oid')
            ->join('__USERINFO__ u','u.uid=p.uid')
            ->where('type',3)->sum('account');
        $currentDate = time()-$this->conf['time_profit']*60;
        $page = $balData->render();
        $balData = $balData->toArray();
        $balance = [];
        foreach ($balData['data'] as $key => $val){
            $val['disabled'] = 0;
            if($val['bptype'] == 7 || ($val['collocation'] == 0 && $currentDate > $val['bptime'])){
                $val['disabled'] = 1;
            }
            $balance[$key] = $val;
        };
        $this->assign('selectType',$selectType);
        $this->assign('payType',$payType);
        $this->assign('page',$page);
        $this->assign('balance',$balance);
        $this->assign('getdata',$getdata);
        $pay_bl=Db::name('config')->where('name','paz_bl')->value('value');
        $this->assign('all_bpprice',$all_bpprice/$pay_bl);
        $this->assign('pay_bl',$pay_bl);
        return $this->fetch();
    }

    /**
     * 配置收益
     * @return mixed|string
     */
    public function collocation(){
        if(input('get.')){
            $data = input('get.');
            $colloc = [];
            $collocKey = 'colloc_'.$data['bpid'];
            if(cache($collocKey)){
                $colloc = cache($collocKey);
            }
            $this->assign('colloc', $colloc);
            $this->assign('data', $data);
        }
        return $this->fetch();
    }

    public function profit(){
        if(input('post.')){
            $post = input('post.');
            $collocKey = 'colloc_'.$post['bpid'];
            if(!cache($collocKey)){
                cache($collocKey, $post);
            }
            $data['bptime'] = $starttime = strtotime($post['starttime']);  //开始时间
            $data['bptype'] = 8;                                           //正在充值中
            $data['remarks'] = '自动收益涨跌';
            $data['bpprice'] = $post['money'];                             //当笔充值的金额
            $data['uid'] = $post['uid'];
            $data['bpbalance'] = 0;
            $data['isverified'] = 1;
            $post['times']= preg_replace("/(\')|(')|(，)/" ,',' , $post['times']);
            $times = explode(',', $post['times']);
            $post['scale']= preg_replace("/(\')|(')|(，)/" ,',' , $post['scale']);
            $scale = explode(',', $post['scale']);
            $arr = [
                'times' => [],
                'scale' => [],
            ];
            //去除存在空格或者多个符号，造成值为空
            if(!empty($times)){
                $i = 0;
                foreach ($times as $val){
                    $val = trim($val);
                    if($val != ''){
                        $arr['times'][$i] = $starttime+$val*60;
                        $i++;
                    }
                }
            }
            if(!empty($scale)){
                $i = 0;
                foreach ($scale as $key=>$val){
                    $val = trim($val);
                    if($val != ''){
                        $arr['scale'][$i] = $val;
                        $i++;
                    }
                }
            }
            if(count($arr['scale']) != count($arr['times'])){
                return WPreturn('时间间隔和盈亏比例个数不对等，或者格式不正确', -1);
            }
            foreach ($arr['times'] as $key=>$val){
                $data['cltime'] = $val;
                $data['scale'] = $arr['scale'][$key];
                Db::name('balance')->insertGetId($data);
            }
            $where['bpid'] = $post['bpid'];
            $where['uid'] = $data['uid'];
            $update['collocation'] = 1;
            $updateBal = Db::name('balance')->where($where)->update($update);
            unset($post);
            unset($data);
            unset($arr);
            unset($where);
            if(!$updateBal){
                return WPreturn('配置失败，请重试!', -1);
            }else{
                return WPreturn('配置成功', 1);
            }
        }
    }

    public function execute(){
        $balance = Db::name('balance')->field('bpid,bpprice,uid,cltime,scale')->where('bptype', 8)->select();
        $currentDate = time();
        if(!empty($balance)){
            foreach ($balance as $key=>$val){
                if($currentDate >= $val['cltime']){
                    $user = db('userinfo')->field('usermoney')->where('uid', $val['uid'])->find();
                    $b_data['bptype'] = 7;
                    $b_data['bpprice'] = $val['bpprice'] * $val['scale']/100; //充值金额*收益比例
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
        }else{
            return '暂无需要收益的会员';
        }
    }
    /**
     * 提现
     * @author lukui  2017-02-16
     * @return [type] [description]
     */
    public function cash()
    {
        Log::info("........................");
        Log::info("开始执行 ".time());
        $pagenum = cache('page');
        $getdata = $where = array();
        $data = input('');
        //类型
        if(isset($data['isverified']) && $data['isverified'] != ''){
            $where['isverified']=$data['isverified'];
            $getdata['isverified'] = $data['isverified'];
        }

        //用户名称、id、手机、昵称
        if(isset($data['username']) && !empty($data['username'])){
            $data['username'] = trim($data['username']);
            if($data['stype'] == 1){
                $where['username|u.uid|utel|nickname'] = array('like','%'.$data['username'].'%');
            }
            if($data['stype'] == 2){
                $puid = db('userinfo')->where(array('username'=>$data['username']))->whereOr('utel',$data['username'])->value('uid');
                if(!$puid) $puid = 0;
                $where['u.oid'] = $puid;
            }


            $getdata['username'] = $data['username'];
            $getdata['stype'] = $data['stype'];
        }

        //时间搜索
        if(isset($data['starttime']) && !empty($data['starttime'])){
            if(!isset($data['endtime']) || empty($data['endtime'])){
                $data['endtime'] = date('Y-m-d H:i:s',time());
            }
            $where['bptime'] = array('between time',array($data['starttime'],$data['endtime']));
            $getdata['starttime'] = $data['starttime'];
            $getdata['endtime'] = $data['endtime'];
        }

        //权限检测
        if($this->otype != 3){

            $uids = myuids($this->uid);
            if(!empty($uids)){
                $where['u.uid'] = array('IN',$uids);
            }
        }

        Log::info("........................");
        Log::info("数据查询 ".time());

        $balance = Db::name('balance')->alias('b')->field('b.*,u.username,u.nickname,u.oid,u.managername,c.accntnm,c.accntno,c.bankno,bs.bank_nm')
            ->join('__USERINFO__ u','u.uid=b.uid')
            ->join('__BANKCARD__ c','c.uid=b.uid')
            ->join('__BANKS__ bs','bs.id=c.bankno')
            ->where(function ($query){
                $query->where('bptype',0)->whereor('bptype',5)->whereor('bptype',6);
            })
            ->where($where)->order('bpid desc')->paginate($pagenum,false,['query'=> $getdata]);

        Log::info("........................");
        Log::info("查询结束 ".time());

        $this->assign('balance',$balance);
        $this->assign('getdata',$getdata);

        $all_cash = Db::name('balance')->alias('b')->field('b.*,u.username,u.nickname,u.oid')
            ->join('__USERINFO__ u','u.uid=b.uid')
            ->where($where)->sum('bpprice');
        //dump($balance);
        $this->assign('all_cash',$all_cash);
        return $this->fetch();
    }







    /**
     * 转账截图
     */
    public function zzjt(){
        $data=input('');
        $zzjt=  Db::name('zzjt')
            ->where('bpid','=',$data['bpid'])
            ->find();
        if($zzjt){
        } else{
            //无记录添加记录
            $balance=Db::name('balance')->where('bpid','=',$data['bpid'])->find();
            $bankcard = Db::name('bankcard')->where('uid','=',$data['uid'])->find();//银行
            $banks= Db::name('banks')->where('id','=',$bankcard['bankno'])->find();//银行


            $zzjt= [
                'bpid'=>$data['bpid'],//提现ID
                'pch'=>$balance['bptime'],//转账批次号
                'zcdw'=>'转出单位',
                'zczh'=>'转出账户',
                'zhdq'=>'转出地区',
                'skxm'=>$bankcard['accntnm'],//收款姓名
                'skyh'=>$banks['bank_nm'],//银行名称
                'skzh'=> $bankcard['accntno'],//银行卡账号
                'bz'=>'人名币',//币种
                'zzje'=>$balance['bpprice'],//转账金额
                'zzsj'=>$balance['bptime'],//转账时间
                'zzlx'=>'签约金融企业--预约转账',//转账类型
                'zxfs'=>'实时到账',//执行方式
                'states'=>'转账失败',//状态
                'yhbz'=>'',//银行备注
                'cljg'=>'',//处理结果
                'remarks'=>''//用户备注
            ];
            $zzjt['id']=Db::name('zzjt')->insertGetId($zzjt);
        }
        $this->assign('zzjt',$zzjt);
        return $this->fetch();
    }
    /**
     * 修改转账截图
     */
    public function xgzzjt(){
        $data = input('post.');
        $data['zzsj']=strtotime($data['zzsj']);
        Db::name('zzjt')->where('id',$data['id'])->update($data);
    }



    /**
     * 提现处理
     * @author lukui  2017-02-16
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function dorecharge()
    {
        if(input('post.')){
            $data = input('post.');
            //获取提现订单信息和个人信息
            $balance = Db::name('balance')->field('bpid,bpprice,isverified,bptime,reg_par,bpbalance')->where('bpid',$data['bpid'])->find();
            $userinfo = Db::name('userinfo')->field('usermoney')->where('uid',$data['uid'])->find();
            if(empty($userinfo)){
                return WPreturn('提现失败，缺少用户ID参数!',-1);
            }
            if (empty($balance)) {
                return WPreturn('提现失败，缺少订单参数!',-1);
            }
            if($balance['isverified'] != 0){
                //return WPreturn('此订单已操作',-1);
            }

            //提现功能实现：
            $_data['bpid'] = $data['bpid'];
            $_data['isverified'] = (int)$data['type'];
            $_data['cltime'] = time();
            $_data['remarks'] = trim($data['cash_content']);
            if ($data['type'] == 1) {
                $_data['bptype'] = 5;
            } else if ($data['type'] == 2) {
                $_data['bptype'] = 6;
                $update['usermoney'] = $_data['bpbalance'] = $userinfo['usermoney']*1 + $balance['bpprice']*1;
            }
            //提现代付
            /*if($_data['isverified'] == 1){		//同意
               $bank = db('bankcard')->alias('bc')->field('bc.*,bs.bank_nm')
                       ->join('__BANKS__ bs','bs.id=bc.bankno')
                       ->where('uid',$data['uid'])
                       ->find();
               $api = controller('Api');
               $resdafu = $api->daifu($balance,$userinfo,$bank);
               if($resdafu['type'] == -1){
                   return $resdafu;
               }else{
                   $_data['isverified'] == 4;	//代付中……
               }
           }*/
            $ids = Db::name('balance')->update($_data);
            if($ids){
                if($_data['isverified'] == 2){  //拒绝
                    $_ids = Db::name('userinfo')->where('uid', $data['uid'])->update($update);
                    if($_ids){
                        //资金日志
                        set_price_log($data['uid'],5,$balance['bpprice'],'提现','拒绝申请：'.$data['cash_content'],$data['bpid'],$update['usermoney']);
                    }else{
                        return WPreturn('提现拒绝后，增加金额失败，请重试!',-1);
                    }
                }elseif($_data['isverified'] == 1){		//同意
                    //资金日志
                    set_price_log($data['uid'],4,$balance['bpprice'],'提现','提现成功：'.$data['cash_content'],$data['bpid'],$userinfo['usermoney']);
                }else{
                    return WPreturn('操作失败2！',-1);
                }
                return WPreturn('操作成功！',1);

            }else{
                return WPreturn('操作失败1！',-1);
            }
            //验证是否提现成功，成功后修改订单状态
        }else{
            $this->redirect('user/userprice');
        }
    }

    /**
     * 客户资料审核
     * @author lukui  2017-02-16
     * @return [type] [description]
     */
    public function userinfo()
    {
        if(input('post.')){
            $data = input('post.');
            if(!$data['cid']){
                return WPreturn('审核失败,参数错误!',-1);
            }
            $editid = Db::name('cardinfo')->update($data);

            if ($editid) {
                return WPreturn('审核处理成功!',1);
            }else{
                return WPreturn('审核处理失败,请重试!',-1);
            }
        }else{
            $pagenum = cache('page');
            $getdata = $where = array();
            $data=input('get.');
            $is_check = input('param.is_check');
            //类型
            if(isset($data['is_check']) && $data['is_check'] != ''){
                $is_check = $data['is_check'];
            }
            if(isset($is_check) && $is_check != ''){
                $where['is_check']=$is_check;
                $getdata['is_check'] = $is_check;
            }

            //用户名称、id、手机、昵称
            if(isset($data['username']) && !empty($data['username'])){
                $where['username|u.uid|utel|nickname'] = array('like','%'.$data['username'].'%');
                $getdata['username'] = $data['username'];
            }

            //时间搜索
            if(isset($data['starttime']) && !empty($data['starttime'])){
                if(!isset($data['endtime']) || empty($data['endtime'])){
                    $data['endtime'] = date('Y-m-d H:i:s',time());
                }
                $where['ctime'] = array('between time',array($data['starttime'],$data['endtime']));
                $getdata['starttime'] = $data['starttime'];
                $getdata['endtime'] = $data['endtime'];
            }


            $cardinfo = Db::name('cardinfo')->alias('c')->field('c.*,u.username,u.nickname,u.oid,u.portrait,u.utel')
                ->join('__USERINFO__ u','u.uid=c.uid')
                ->where($where)->order('cid desc')->paginate($pagenum,false,['query'=> $getdata]);

            $this->assign('cardinfo',$cardinfo);
            $this->assign('getdata',$getdata);
            return $this->fetch();
        }

    }


    /**
     * 会员列表
     * @author lukui  2017-02-16
     * @return [type] [description]
     */
    public function vipuserlist()
    {
        $pagenum = cache('page');
        $data = input('param.');
        $getdata = array();
        //用户名称、id、手机、昵称
        if(isset($data['username']) && !empty($data['username'])){
            $where['username|uid|utel|nickname'] = array('like','%'.$data['username'].'%');
            $getdata['username'] = $data['username'];
        }

        $oid = input('oid');
        if($oid){
            $where['oid'] = $oid;
            $getdata['oid'] = $oid;
        }

        //权限检测
        if($this->otype != 3){
            $oids = myoids($this->uid);
            $oids[] = $this->uid;
            if(!empty($oids)){
                $where['uid'] = array('IN',$oids);
            }
        }

        $where['otype'] = 101;
        //dump($where);
        $userinfo = Db::name('userinfo')->where($where)->order('uid desc')->paginate($pagenum,false,['query'=> $getdata]);

//                $info=$userinfo->all(); //转换成数组
//                foreach($info as $key =>$val){
//                    //计算二级代理金额
//                    $two_oid=$val['uid'];
//                    $two_money=Db::query("select sum(usermoney) total_money from wp_userinfo where oid=$two_oid ");
//                    $two_uid=Db::query("select uid ,otype from wp_userinfo where oid=$two_oid ");
//
//                    $info[$key]['total_money']=$val['usermoney']+$two_money[0]['total_money'];
//
//                    //计算三级代理金额
//                    foreach($two_uid as $k =>$v){
//                        if($v['otype']==101){  //如果是代理商身份
//                            $three_oid=$v['uid'];
//                            $total_money=Db::query("select sum(usermoney) as total_money  from wp_userinfo where oid=$three_oid ");
//                            $info[$key]['total_money']=$info[$key]['total_money']+$total_money[0]['total_money'];
//                        }
//                    }
//                }



        $this->assign('userinfo',$userinfo);
        $this->assign('getdata',$getdata);
        return $this->fetch();
    }

    /**
     * 添加会员
     * @author lukui  2017-02-16
     * @return [type] [description]
     */
    public function vipuseradd()
    {

        if(input('post.')){
            $data = input('post.');
            $data['utime'] = time();
            $data['comname'] = trim($data['upwd']);
            $data['upwd2'] = $data['upwd'] = md5(trim($data['upwd']));
            $_this_user = db('userinfo')->where('uid',$this->uid)->find();
            //判断用户是否存在
            $data['username'] = trim($data['username']);
            $c_uid = Db::name('userinfo')->where('username',$data['username'])->value('uid');
            if($c_uid){
                return WPreturn('此用户已存在，请更改用户名!',-1);
            }

            $issetutl = db('userinfo')->where('utel',$data['utel'])->find();
            if($issetutl){
                return WPreturn('该手机号已存在!',-1);
            }
            //佣金比例(手续费)
            if($this->otype == 3){
                if($data['rebate'] > 100){
                    return WPreturn('红利比例不得大于100!',-1);
                }
            }else{
                if($_this_user['rebate'] <= $data['rebate']){
                    return WPreturn('红利比例不得大于'.$_this_user['rebate'].'!',-1);
                }
            }

            //红利比例(下单)
            if($this->otype == 3){
                if($data['feerebate'] > 100){
                    return WPreturn('佣金比例不得大于100!',-1);
                }
            }else{
                if($_this_user['feerebate'] <= $data['feerebate']){
                    return WPreturn('佣金比例不得大于'.$_this_user['feerebate'].'!',-1);
                }
            }

            //去除空数组
            $data = array_filter($data);
            $data['oid'] = $_SESSION['userid'];
            $data['managername'] = db('userinfo')->where('uid',$data['oid'])->value('username');
            $data['otype'] = 101;
            $ids = Db::name('userinfo')->insertGetId($data);
            if ($ids) {
                return WPreturn('添加代理成功!',1);
            }else{
                return WPreturn('添加代理失败,请重试!',-1);
            }
        }else{
            //所有经理
            $jingli = Db::name('userinfo')->field('uid,username')->where('otype',2)->order('uid desc')->select();
            $this->assign('isedit',0);
            $this->assign('jingli',$jingli);
            return $this->fetch();
        }
    }

    /**
     * 编辑会员
     * @author lukui  2017-02-16
     * @return [type] [description]
     */
    public function vipuseredit()
    {
        if(input('post.')){
            //exit;
            $data = input('post.');
            if(!isset($data['uid']) || empty($data['uid'])){
                return WPreturn('参数错误,缺少用户id!',-1);
            }
            $foid = db('userinfo')->where('uid',$data['uid'])->value('oid');
            $_this_user = db('userinfo')->where('uid',$foid)->find();
            //佣金比例(手续费)
            if($this->otype == 3){
                if($data['rebate'] > 100){
                    return WPreturn('红利比例不得大于100!',-1);
                }
            }else{
                if($_this_user['rebate'] < $data['rebate']){
                    return WPreturn('红利比例不得大于'.$_this_user['rebate'].'!',-1);
                }
            }
            //红利比例(下单)
            if($this->otype == 3){
                if($data['feerebate'] > 100){
                    return WPreturn('佣金比例不得大于100!',-1);
                }
            }else{
                if($_this_user['feerebate'] < $data['feerebate']){
                    return WPreturn('佣金比例不得大于'.$_this_user['feerebate'].'!',-1);
                }
            }
            //修改密码
            if(isset($data['upwd']) && !empty($data['upwd'])){
                //验证用户密码
                $c_user = Db::name('userinfo')->where('uid',$data['uid'])->find();
                $utime = $c_user['utime'];
                if(!isset($data['upwd']) || empty($data['upwd'])){
                    return WPreturn('如需修改密码请输入新密码!',-1);
                }
                if(isset($data['upwd']) && isset($data['upwd2']) && $data['upwd'] != $data['upwd2']){
                    return WPreturn('两次输入密码不同!',-1);
                }
                $data['comname'] = trim($data['upwd']);
                $data['upwd2'] = $data['upwd'] = md5(trim($data['upwd']));
            }
            if(empty($data['upwd'])){unset($data['upwd']);unset($data['upwd2']);}
            if($this->otype == 3){
                if(empty($data["usermoney"])){
                    $data["usermoney"] = 0;
                }
                $_data_user = db('userinfo')->where('uid',$data['uid'])->find();
                if($data['usermoney'] != $_data_user['usermoney']){
                    $b_data['bptype'] = 2;
                    $b_data['bptime'] = $b_data['cltime'] = time();
                    $b_data['bpprice'] = $data['usermoney'] - $_data_user['usermoney'] ;
                    //	$b_data['remarks'] = '后台管理员id'.$_SESSION['userid'].'编辑客户信息改动金额';
                    $b_data['remarks'] = '系统审核通过充值';
                    $b_data['uid'] = $data['uid'];
                    $b_data['isverified'] = 1;
                    $b_data['bpbalance'] = $data['usermoney'];
                    $addbal = Db::name('balance')->insertGetId($b_data);
                    if(!$addbal){
                        return WPreturn('增加金额失败，请重试!',-1);
                    }
                }
            }
            $data['ustatus']--;
            $editid = Db::name('userinfo')->update($data);

            if ($editid) {
                return WPreturn('修改用户成功!',1);
            }else{
                return WPreturn('如不修改，请返回用户列表!',-1);
            }
        }else{
            $uid = input('param.uid');
            if (!isset($uid) || empty($uid)) {
                $this->redirect('user/vipuserlist');
            }
            //获取用户信息
            $where['uid'] = $uid;
            $userinfo = Db::name('userinfo')->where($where)->find();

            //获取所有经理信息
            $jingli = Db::name('userinfo')->field('uid,username')->where('otype',2)->order('uid desc')->select();


            unset($userinfo['otype']);
            $this->assign($userinfo);
            $this->assign('isedit',1);
            $this->assign('jingli',$jingli);
            return $this->fetch('vipuseradd');
        }
    }


    /**
     * 会员的邀请码
     * @author lukui  2017-02-17
     * @return [type] [description]
     */
    public function usercode()
    {
        if (input('post.')) {
            $data = input('post.');
            $data['usercode'] = trim($data['usercode']);
            //邀请码是否存在
            $codeid = Db::name('usercode')->where('usercode',$data['usercode'])->value('id');
            if($codeid){
                return WPreturn('此邀请码已存在',-1);
            }
            $ids = Db::name('usercode')->insertGetId($data);
            if ($ids) {
                return WPreturn('添加邀请码成功!',1);
            }else{
                return WPreturn('添加邀请码失败,请重试!',-1);
            }
            dump($data);

        }else{
            $uid = input('param.uid');
            if(!isset($uid) || empty($uid)){
                $this->redirect('user/vipuserlist');
            }

            //所有渠道
            $manner = Db::name('userinfo')->field('uid,username')->where('otype',3)->order('uid desc')->select();

            //所有邀请码
            $usercode = Db::name('usercode')->alias('uc')->field('uc.*,ui.username')
                ->join('__USERINFO__ ui','ui.uid=uc.mannerid')
                ->where('uc.uid',$uid)->order('id desc')->select();

            $this->assign('uid',$uid);
            $this->assign('manner',$manner);
            $this->assign('usercode',$usercode);
            return $this->fetch();
        }
    }



    /**
     * 会员资金管理
     * @author lukui  2017-02-17
     * @return [type] [description]
     */
    public function vipuserbalance()
    {
        $pagenum = cache('page');
        $getdata = $userinfo = array();
        $data = input('get.');

        //用户名称、id、手机、昵称
        if(isset($data['username']) && !empty($data['username'])){
            $where['username|uid|utel|nickname'] = array('like','%'.$data['username'].'%');
            $getdata['username'] = $data['username'];
        }

        //时间搜索
        if(isset($data['starttime']) && !empty($data['starttime'])){
            if(!isset($data['endtime']) || empty($data['endtime'])){
                $data['endtime'] = date('Y-m-d H:i:s',time());
            }
            $u_where['bptime'] = array('between time',array($data['starttime'],$data['endtime']));
            $getdata['starttime'] = $data['starttime'];
            $getdata['endtime'] = $data['endtime'];
        }

        //会员类型 otype
        if(isset($data['otype']) && !empty($data['otype'])){
            $where['otype'] = $data['otype'];
            $getdata['otype'] = $data['otype'];
        }else{
            $where['otype'] = array('IN',array(2,3,4));
        }

        //必须是已经审核了的
        $u_where['isverified'] = 1;

        $user = Db::name('userinfo')->field('uid,username,oid,otype')->where($where)->order('uid desc')->paginate($pagenum,false,['query'=> $getdata]);

        //分页与数据分开执行
        $page = $user->render();
        $userinfo = $user->items();

        //获取会员下面客户的资金情况
        foreach ($userinfo as $key => $value) {
            $u_uid = array();
            //获取会员的客户id
            if($value['otype'] == 2){  //经理
                $u_uid = JingliUser($value['uid']);
            }elseif($value['otype'] == 3){  //渠道
                $u_uid = QudaoUser($value['uid']);
            }elseif($value['otype'] == 4){  //员工
                $u_uid = YuangongUser($value['uid']);
            }
            if(empty($u_uid)){
                $u_uid = array(0);
            }
            $u_where['uid'] = array('IN',$u_uid);
            //总充值
            $u_where['bptype'] = 1;
            $userinfo[$key]['recharge'] = Db::name('balance')->where($u_where)->sum('bpprice');
            //总提现
            $u_where['bptype'] = 0;
            $userinfo[$key]['getprice'] = Db::name('balance')->where($u_where)->sum('bpprice');
            //总净入
            $userinfo[$key]['income'] = $userinfo[$key]['recharge'] - $userinfo[$key]['getprice'];


        }

        //dump($userinfo);
        $this->assign('userinfo',$userinfo);
        $this->assign('page', $page);
        $this->assign('getdata',$getdata);
        return $this->fetch();
    }


    /**
     * 客户资金管理
     * @author lukui  2017-02-17
     * @return [type] [description]
     */
    public function userbalance()
    {
        $pagenum = cache('page');

        //所有归属
        $vipuser['jingli'] = Db::name('userinfo')->field('uid,username')->where('otype',2)->select();
        $vipuser['qudao'] = Db::name('userinfo')->field('uid,username')->where('otype',3)->select();
        $vipuser['yuangong'] = Db::name('userinfo')->field('uid,username')->where('otype',4)->select();
        //搜索条件
        $where = $getdata = array();
        $data = input('get.');
        //用户名称、id、手机、昵称
        if(isset($data['username']) && !empty($data['username'])){
            $where['username|u.uid|utel|nickname'] = array('like','%'.$data['username'].'%');
            $getdata['username'] = $data['username'];
        }

        //时间搜索
        if(isset($data['starttime']) && !empty($data['starttime'])){
            if(!isset($data['endtime']) || empty($data['endtime'])){
                $data['endtime'] = date('Y-m-d H:i:s',time());
            }
            $where['bptime'] = array('between time',array($data['starttime'],$data['endtime']));
            $getdata['starttime'] = $data['starttime'];
            $getdata['endtime'] = $data['endtime'];
        }

        //会员类型 ouid
        if(isset($data['ouid']) && !empty($data['ouid'])){
            //该会员下所有的邀请码
            $uids = UserCodeForUser($data['ouid']);
            if(empty($uids)){
                $uids = array(0);
            }
            $where['b.uid'] = array('IN',$uids);
        }

        //必须是已经审核了的
        $where['isverified'] = 1;


        $where['bptype'] = array('between','0,2');
        //客户资金变动
        $balance = Db::name('balance')->alias('b')->field('b.*,u.username,u.nickname,u.oid')
            ->join('__USERINFO__ u','u.uid=b.uid')
            ->where($where)->order('bpid desc')->paginate($pagenum,false,['query'=> $getdata]);

        $this->assign('vipuser',$vipuser);
        $this->assign('balance',$balance);
        return $this->fetch();
    }

    /**
     * 添加管理员
     * @author lukui  2017-02-17
     * @return [type] [description]
     */
    public function adminadd()
    {

        return $this->fetch();
    }

    /**
     * 管理员列表
     * @author lukui  2017-02-17
     * @return [type] [description]
     */
    public function adminlist()
    {

        return $this->fetch();
    }






    /**
     * 禁用、启用用户
     * @return [type] [description]
     */
    public function doustatus()
    {

        $post = input('post.');
        if(!$post){
            $this->error('非法操作！');
        }



        $ids = db('userinfo')->update($post);
        if($ids){
            return WPreturn('操作成功！',1);
        }else{
            return WPreturn('操作失败！',-1);
        }


    }

    /**
     * 成为代理商
     * @return [type] [description]
     */
    public function dootype()
    {

        $post = input('post.');
        if(!$post){
            $this->error('非法操作！');
        }

        if(!$post['uid'] || !in_array($post['otype'], [0, 101])){
            return WPreturn('参数错误',-1);
        }

        $ids = db('userinfo')->update($post);
        if($ids){
            return WPreturn('操作成功！',1);
        }else{
            return WPreturn('操作失败！',-1);
        }


    }


    /**
     * 签约管理
     * @return [type] [description]
     */
    public function userbank()
    {
        $uid = input('param.uid');
        if(!$uid){
            $this->error('参数错误！');
        }
        //gb
        $bank = db('bankcard')->alias('bc')->field('bc.*,bs.bank_nm')
            ->join('__BANKS__ bs','bs.id=bc.bankno')
            ->where('uid',$uid)
            ->find();

        $banks = db('banks')->select();
        $this->assign('uid',$uid);
        $this->assign('banks',$banks);
        $this->assign('bank',$bank);
        return $this->fetch();
    }


    /**
     * 我的团队
     * @return [type] [description]
     */
    public function myteam()
    {

        $uid = $this->uid;
        $userinfo = db('userinfo');
        //$myteam = $userinfo->field('uid,oid,username,utel,nickname,usermoney')->where(array('oid'=>$uid,'otype'=>101))->select();
        $myteam = mytime_oids($uid);
        $user = $userinfo->where('uid',$uid)->find();
        $user['mysons'] = $myteam;
        $this->assign('mysons',$user);
        return $this->fetch();

    }






    /**
     * 某个代理商的业绩
     * @return [type] [description]
     */
    public function yeji()
    {
        $userinfo = db('userinfo');
        $price_log = db('price_log');
        $uid = input('uid');
        if(!$uid){
            $this->error('参数错误！');
        }

        $_user = $userinfo->where('uid',$uid)->find();
        if(!$_user){
            $this->error('暂无用户！');
        }



        //搜索条件
        $data = input('param.');

        if(isset($data['starttime']) && !empty($data['starttime'])){
            if(!isset($data['endtime']) || empty($data['endtime'])){
                $data['endtime'] = date('Y-m-d H:i:s',time());
            }
            $getdata['starttime'] = $data['starttime'];
            $getdata['endtime'] = $data['endtime'];
        }else{
            $getdata['starttime'] = date('Y-m-d',time()).' 00:00:00';
            $getdata['endtime'] = date('Y-m-d',time()).' 23:59:59';
        }

        $map['time'] = array('between time',array($getdata['starttime'],$getdata['endtime']));
        $map['uid'] = $uid;
        /*
        //红利收益
        $map['title'] = '对冲';
        $hl_account = $price_log->where($map)->sum('account');
        if(!$hl_account) $hl_account = 0;
        //佣金收益
        $map['title'] = '客户手续费';
        $yj_account = $price_log->where($map)->sum('account');
        if(!$yj_account) $yj_account = 0;
        //dump($yj_account);

        $this->assign('_user',$_user);
        $this->assign('getdata',$getdata);
        $this->assign('all_sxfee',$yj_account);
        $this->assign('all_ploss',$hl_account);
        */

        $_map['buytime'] = array('between time',array($getdata['starttime'],$getdata['endtime']));
        $uids = myuids($uid);
        $_map['uid']  = array('IN',$uids);
        $all_sxfee = db('order')->where($_map)->sum('sx_fee');
        if(!$all_sxfee) $all_sxfee = 0;
        $all_ploss = db('order')->where($_map)->sum('ploss');
        if(!$all_ploss) $all_ploss = 0;

        $this->assign('_user',$_user);
        $this->assign('getdata',$getdata);
        $this->assign('all_sxfee',$all_sxfee);
        $this->assign('all_ploss',$all_ploss);

        /*
        $this->assign('hl_account',$hl_account);
        $this->assign('yj_account',$yj_account);
        */
        return $this->fetch();
    }


    /**删除用户
     */
    public function deleteuser()
    {

        $uid = input('post.uid');
        if(!$uid){
            return WPreturn('参数错误！',-1);
        }

        $ids = db('userinfo')->where('uid',$uid)->delete();
        if($uid){
            return WPreturn('删除成功',1);
        }else{
            return WPreturn('删除失败',-1);
        }
    }

    public function chongzhi()
    {
        return $this->fetch();
    }

    public function addprice()
    {
        $post = input('post.');
        $post['utel'] = trim($post['utel']);
        $post['bpprice'] = trim($post['bpprice']);
        if(!$post || !$post['bpprice']){return WPreturn('请正常填写参数',-1);}
        $user = db('userinfo')->field('uid,usermoney')->where('utel', $post['utel'])->find();
        if(!$user) return WPreturn('此用户不存在，请正确填写用户手机号',-1);
        $b_data['bptype'] = 2;
        $b_data['bptime'] = $b_data['cltime'] = time();
        $b_data['bpprice'] = $post['bpprice'] ;
        //$b_data['remarks'] = '后台管理员id'.$_SESSION['userid'].'编辑客户信息改动金额';
        $b_data['remarks'] = '系统审核通过充值';
        $b_data['uid'] = $user['uid'];
        $b_data['isverified'] = 1;
        $update['usermoney'] = $b_data['bpbalance'] = $user['usermoney']*1+$post['bpprice']*1;
        $ids = Db::name('userinfo')->where('utel',$post['utel'])->update($update);
        if(!$ids) return WPreturn('增加金额失败，请重试!',-1);
        $addbal = Db::name('balance')->insertGetId($b_data);
        if(!$addbal){
            return WPreturn('系统错误，请核对订单!',-1);
        }else{
            $p_date['uid'] = $user['uid'];
            $p_date['oid'] = $addbal;
            $p_date['type'] = 3;
            $p_date['account'] = $post['bpprice'];
            $p_date['title'] = '充值';
            $p_date['content'] = '手动充值';
            $p_date['time'] = time();
            $p_date['nowmoney'] = $b_data['bpbalance'];
            \db('price_log')->insert($p_date);
            return WPreturn('操作成功',1);
        }
    }

    public function addprice1(){
        //exit;
        $post = input('post.');
        $post['uid'] = trim($post['uid']);
        $post['bpid'] = trim($post['bpid']);
        $post['isverified'] = trim($post['isverified']);
        $post['bptype'] = trim($post['bptype']);
        if($post['isverified'] == '2'){
            $post['cltime'] = time();
            $addbal = Db::name('balance')->update($post);
            return WPreturn('拒绝成功!',-1);
        }

        if(!$post){
            return WPreturn('请正常填写参数',-1);
        }
        $user = db('userinfo')->field('usermoney')->where('uid',$post['uid'])->find();
        if(!$user) return WPreturn('此用户不存在，请正确填写用户手机号',-1);
        $order = db('balance')->where('bpid',$post['bpid'])->find();
        $pay_bl=Db::name('config')->where('name','paz_bl')->value('value');
        $b_data['bptype'] = 1;
        $b_data['cltime'] = time();
        $b_data['bpprice'] = $order['bpprice'] ;
        $b_data['uid'] = $post['uid'];
        $b_data['isverified'] = 1;
        $update['usermoney'] = $b_data['bpbalance'] = $user['usermoney']*1+$order['bpprice']/$pay_bl;
        $ids = Db::name('userinfo')->where('uid', $post['uid'])->update($update);
        if(!$ids) return WPreturn('增加金额失败，请重试!',-1);
        $addbal = Db::name('balance')->where('bpid', $post['bpid'])->update($b_data);
        if(!$addbal){
            return WPreturn('系统错误，请核对订单!',-1);
        }else{
            $p_date['uid'] = $post['uid'];
            $p_date['oid'] = $post['bpid'];
            $p_date['type'] = 3;
            $p_date['account'] = $order['bpprice'];
            $p_date['title'] = '充值';
            $p_date['content'] = '用户充值';
            $p_date['time'] = time();
            $p_date['nowmoney'] = $b_data['bpbalance'];
            \db('price_log')->insert($p_date);
            return WPreturn('操作成功',1);
        }
    }
}
