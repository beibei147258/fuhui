<?php
namespace app\index\controller;
use think\Db;
use think\Log;


class Order extends Base
{
   protected  $fengkong=0;
	/**
	 * 下单
	 * @author lukui  2017-07-20
	 * @return [type] [description]
	 */
	public function addorder()
	{
		$data = input('post.');
		
		$adddata['uid'] = $data['uid']=$this->uid;
		$conf = $this->conf;
		//持仓限制
		$allfee = Db::name('order')->where(array('ostaus'=>0,'uid'=>$data['uid']))->sum('fee');
		$allfee = $allfee?$allfee:0;
		/*
		if($allfee+$data['order_price'] > getconf('order_max_price')){
			return WPreturn('持仓最大为'.getconf('order_max_price').'！',-1);
		}
		*/
		if($data['order_price'] > $conf['order_max_price']){
			return WPreturn('单笔持仓最大为'.$conf['order_max_price'].'！',-1);
		}
		$allcount = Db::name('order')->where(array('ostaus'=>0,'uid'=>$data['uid']))->count();
		if($allcount >  $conf['max_order_count']){
			return WPreturn('最大持仓数量为'.$conf['max_order_count'].'！',-1);
		}
		//验证是否开市
		
		//用户信息
		$user = Db::name('userinfo')->field('ustatus,usermoney,uid')->where('uid',$data['uid'])->find();
		//验证用户是否被冻结
		if($user['ustatus'] != 0){
			return WPreturn('账户异常，详情请咨询在线客服！',-1);
		}

		//手续费
		$web_poundage = round($data['order_price']*$conf['web_poundage']/100,2);
		
		//验证余额是否够
		if($user['usermoney'] < $data['order_price'] + $web_poundage){
			return WPreturn('您得余额不足，请充值！',-1);
		}
		//验证金额 20 ~ 5000
		if($data['order_price'] < $conf['order_min_price'] || $data['order_price'] > $conf['order_max_price']){
			return WPreturn('抱歉！单笔持仓在'.$conf['order_min_price'].'~'.$conf['order_max_price'].'之间！',-1);
		}
		//建仓
		$adddata['buytime'] = time();
		$adddata['endprofit'] = $data['order_sen'];
		$adddata['pid'] = $data['order_pid'];
		$adddata['ostyle'] = $data['order_type'];
		$adddata['buyprice'] = $data['newprice'];
		$adddata['endloss'] = $data['order_shouyi'];
		$adddata['eid'] = 2;
		$adddata['selltime']=$adddata['buytime']+$adddata['endprofit'];
		$adddata['fee'] = $data['order_price'];
        $adddata['ptitle'] = db('productinfo')->where('pid',$data['order_pid'])->value('ptitle');
        $adddata['ostaus']='0';
        $adddata['sx_fee']=$web_poundage;

        $allfee = $adddata['fee'] + $adddata['sx_fee'];
        //会员建仓后金额
        $adddata['commission'] = $user['usermoney'] - $allfee;
        //订单号
        $adddata['orderno']=date('YmdHis').rand(1111,9999);
        
        //下单
        $ids = Db::name('order')->insertGetId($adddata);
        $risk = Db::name('risk')->find();
        if($ids){
        	//下单成功减用户余额 
        	$u_fee = $allfee;
        	$editmoney = Db::name('userinfo')->where('uid',$data['uid'])->setDec('usermoney',$u_fee);

        	$nowmoney = $adddata['commission'];
        	if($nowmoney < 0) $nowmoney=0;
        	set_price_log($data['uid'],2,$u_fee,'下单','下单成功',$ids,$nowmoney);
        	
        	if($editmoney){
        		$adddata['oid'] = $ids;
        		$order_rand = rand(1,1000);
        		cache('goorder_'.$ids,$order_rand,$adddata['endprofit']+10);
        		$adddata['order_rand'] = $order_rand;

        		$adddata['lb'] = $risk['lb'];
        		$adddata['lt'] = $risk['lt'];
        		$adddata['wb'] = $risk['wb'];
        		$adddata['wt'] = $risk['wt'];

        		$res = base64_encode(json_encode($adddata));
        		return WPreturn($res,1);
        	}else{
        		Db::table('order')->where('oid',$ids)->delete();
        		return WPreturn('下单失败，请重试！',-1);
        	}

        }else{
        	return WPreturn('下单失败，请重试！',-1);
        }
	}


	/**
	 * ajax 通过产品id 获取用户订单，
	 * @author lukui  2017-07-22
	 * @return [type] [description]
	 */
	public function ajaxorder()
	{
		$uid = $_SESSION['uid'];
		$pid = input('param.pid');
		if (empty($uid) || empty($pid)) {
			return false;
		}
		//持仓信息
		$map = array('uid'=>$uid,'ostaus'=>0,'pid'=>$pid);
		$map['selltime'] = array('gt',time());
		$hold = Db::name('order')->where($map)->order('oid desc')->select();
		if($hold){
			$hold[0]['time'] = time();
		}
		if($hold){
			return base64_encode(json_encode($hold));
		}else{
			return false;
		}

		
	}

	/**
	 * ajax 获取用户未平仓订单，
	 * @author lukui  2017-07-22
	 * @return [type] [description]
	 */
	public function ajaxorder_list()
	{
		$uid = $this->uid;
		if (empty($uid)) {
			return false;
		}
		//持仓信息
		$map = array('uid'=>$uid,'ostaus'=>0);
		$map['selltime'] = array('gt',time());
    
		$hold = Db::name('order')->where($map)->order('oid desc')->select();
        $risk = Db::name('risk')->find();

        for ($i = 0; $i < sizeof($hold); $i++) {
            $hold[$i]['lb'] = $risk['lb'];
            $hold[$i]['lt'] = $risk['lt'];
            $hold[$i]['wb'] = $risk['wb'];
            $hold[$i]['wt'] = $risk['wt'];
        }



		   
		if($hold){
			$hold[0]['time'] = time();
		}
		if($hold){
			return base64_encode(json_encode($hold));
		}else{
			return false;
		}

		
	}

	public function get_price()
	{
		

		//此刻产品价格
		$p_map['isdelete'] = 0;
		$pro = db('productdata')->field('pid,Price')->where($p_map)->select();
		$prodata = array();
		foreach ($pro as $k => $v) {
			$prodata[$v['pid']] = $v['Price'];
		}
		return base64_encode(json_encode($prodata));;

	}
	/**
	 * ajax 通过产品id 平仓后弹框提示，
	 * @author lukui  2017-07-22
	 * @return [type] [description]
	 */
	public function ajaxalert()
	{
		$uid = $_SESSION['uid'];
		$pid = input('param.pid');
		if (empty($uid) || empty($pid)) {
			return false;
		}
		//持仓信息
		$hold = Db::name('order')->field('oid,ploss,fee,eid')->where(array('uid'=>$uid,'ostaus'=>1,'pid'=>$pid,'isshow'=>0))->order('oid desc')->find();
		//修改持仓信息
		$isedit = Db::name('order')->where('oid',$hold['oid'])->setField('isshow','1');
		if($hold && $isedit){
			return $hold;
		}else{
			return false;
		}

		
	}


	/**
	 * 持仓列表
	 * @author lukui  2017-07-18
	 * @return [type] [description]
	 */
	public function hold()
	{
		
		$uid = $_SESSION['uid'];		
		$hold = Db::name('order')->field('oid,ptitle,buytime,fee,ostyle')->where(array('uid'=>$uid,'ostaus'=>0))->order('oid desc')->select();
		//p($hold);
		$this->assign('hold',$hold);
		return $this->fetch();
	}


	public function holdinfo()
	{
		$uid = $_SESSION['uid'];
		$oid = input('param.oid');
		if(!$oid){
			$this->redirect('hold');
		}
		$order = Db::name('order')->where('oid',$oid)->find();
		$this->assign($order);
		return $this->fetch();


	}


	/**
	 * 订单列表
	 * @author lukui  2017-07-18
	 * @return [type] [description]
	 */
	public function orderlist()
	{
		$uid = $this->uid;
		$hold = Db::name('order')->where(array('uid'=>$uid,'ostaus'=>1))->order('oid desc')->paginate(20);
		return base64_encode(json_encode($hold));
		
	}

	


	/**
	 * 已平仓订单详情
	 * @author lukui  2017-07-21
	 * @return [type] [description]
	 */
	public function orderinfo()
	{
		$uid = $_SESSION['uid'];
		$oid = input('param.oid');
		if(!$oid){
			$this->redirect('orderlist');
		}
		$order = Db::name('order')->where('oid',$oid)->find();
		$this->assign($order);
		return $this->fetch();
		
	}



	/**
	 * 实时获取以平仓订单
	 * @return [type] [description]
	 */
	public function get_this_order()
	{
		//sleep(2);
		$oid = input('param.oid');
		$map['oid'] = $oid;
		$map['ostaus'] = 1;
		$order = db('order')->where($map)->find();
		
		return base64_encode(json_encode($order));

	}

	/**
	 * 实时获取以平仓订单
	 * @return [type] [description]
	 */
	public function get_hold_order()
	{
		

		$oid = input('param.oid');
		$map['oid'] = $oid;
		$map['ostaus'] = 1;

		
		$order = db('order')->where($map)->find();
		
		return base64_encode(json_encode($order));

	}


	//平仓
	public function goorder(){

		$oid = input('oid');
		$price = input('price');
        $order_rand = 303;
		if(!$oid || !$price || !$order_rand ){
			die('0');
		}


		$order = db('order')->where('oid',$oid)->find();

		//没有此订单
		if(!$order ){
			die('0');
		}


		//没有平仓
		if(isset($order['ostyle']) && $order['ostaus'] == 0){
			die('2');
		}

		//已平仓 但是价格相同
		if(isset($order['sellprice']) && $order['sellprice'] == $price){
			cache('goorder_'.$order['oid'],null);
			die('1');
		}

		//已平仓 但是无效交易
		if(isset($order['is_win']) && $order['is_win'] == 3){
			cache('goorder_'.$order['oid'],null);
			die('1');
		}



		die('3');
		//已平仓 但是价格不相同
		if(isset($order['sellprice']) && $order['sellprice'] != $price){

            //风控参数
            $risk = db('risk')->find();
            $randUp = round(rand($risk['wb'],$risk['wt']),2);
            $randDown = round(rand($risk['lb'],$risk['lt']),2);
			//资金变动日志
			$p_map['title'] = '下单';
			$p_map['oid'] = $order['oid'];
			
			$price_log = db('price_log')->where($p_map)->find();
			/*$price =$this->riskprice($order['fee'], $order['buyprice'], $order['pid'], $order['ostyle'],$price);//对下的订单进行风控
            $price =$this->riskcom($order['uid'], $order['buyprice'], $order['pid'], $order['ostyle'],$price);//在这里风控掉当前的价格
			$price = $this->riskorder($order['kong_type'], $order['buyprice'], $order['pid'], $order['ostyle'],$price);//订单单点的风控*/
			if(!$price_log){
				die('2');
			}
			

			$_data['oid'] = $oid;
			$_data['sellprice'] = $price;
		
			//买跌 赢利 ||  买涨 赢利
			if(($order['ostyle'] == 1 && $order['buyprice'] < $price)  || ($order['ostyle'] == 0 && $order['buyprice'] > $price )){

				$_data['is_win'] = 2;
				$_data['ploss'] = $order['fee']*(-1)*$randDown/100;

				$u_add = 0;

				$d_add = $price_log['account'];
				//db('userinfo')->where('uid',$price_log['uid'])->setDec('usermoney',$d_add);
				$price_log['account'] = round($order['fee']*($randDown/100),2)*(-1);
               	$price_log['type'] = 2;
				db('price_log')->update($price_log);	
               	db('price_log')->where('id',$price_log['id'])->delete();
               	$this->order_price_log($order['oid'],$order);
               	
               	db('order_log')->where('oid',$order['oid'])->delete();
               	//写入日志
	           	$api = controller('api');
	           	$api->set_order_log($order,$u_add);
			}

			//买跌 亏损 ||  买涨 亏损
			if(($order['ostyle'] == 1 && $order['buyprice'] > $price)  || 
				($order['ostyle'] == 0 && $order['buyprice'] < $price )){

				$_data['is_win'] = 1;
				$yingli = $order['fee']*($randUp/100);
				$_data['ploss'] = $yingli;

				//平仓增加用户金额
               	$u_add = $yingli + $order['fee'];
               	db('userinfo')->where('uid',$order['uid'])->setInc('usermoney',$u_add);
               	db('price_log')->where('id',$price_log['id'])->delete();
               	$this->order_price_log($order['oid'],$order);
               	db('order_log')->where('oid',$order['oid'])->delete();
               	//写入日志
	           	$api = controller('api');
	           	$api->set_order_log($order,$u_add);	
			}

			//无效交易
			if( $price == $order['buyprice'] ){
				$_data['ploss'] = 0;
				$_data['is_win'] = 3;
				$u_add = $order['ploss'];
				
				//db('userinfo')->where('uid',$order['uid'])->setDec('usermoney',$u_add);
				
				db('price_log')->where('id',$price_log['id'])->delete();
               	//写入日志
	           	$api = controller('api');
	           	db('order_log')->where('oid',$order['oid'])->delete();
	           	$api->set_order_log($order,$u_add);

				$dbuser = db('userinfo');
				$dbplog = db('price_log');
				$map['oid'] = $oid;
				$map['title'] = "对冲";
				$list = $dbplog->where($map)->select();

				foreach ($list as $key => $value) {
					if($value['account'] > 0){
						$_add = $value['account'];
						$dbuser->where('uid',$value['uid'])->setDec('usermoney',$_add);
					}elseif($value['account'] < 0){
						$_add = $value['account']*(-1);
						$dbuser->where('uid',$value['uid'])->setInc('usermoney',$_add);
					}					
					$_update['id'] = $value['id'];										
					$dbplog->where($_update)->delete();
				}
			}	
			       $_data['kong_type']=$this->fengkong; //被风控了的标记
            //更新数据订单
			db('order')->update($_data);
			cache('goorder_'.$order['oid'],null);
			die('3');
		}
	}



	public function order_price_log($oid,$order)
	{
		if(!$oid || !$order){
			return false;
		}
		$dbuser = db('userinfo');
		$dbplog = db('price_log');
		$map['oid'] = $oid;
		$map['title'] = "对冲";
		$list = $dbplog->where($map)->select();

		
		foreach ($list as $key => $value) {

			
			
			if($value['account'] > 0){
				$_add = $value['account'] + $value['account']*($order['endloss']/100);
				$_update['account'] = $value['account']*($order['endloss']/100)*(-1);
				$dbuser->where('uid',$value['uid'])->setDec('usermoney',$_add);
				$_update['type'] = 2;
			}elseif($value['account'] < 0){
				$_add = $value['account']*(-1) + $value['account']*(-1)/($order['endloss']/100);
				$_update['account'] = $value['account']*(-1)/($order['endloss']/100);
				$_update['type'] = 1;
				$dbuser->where('uid',$value['uid'])->setInc('usermoney',$_add);
			}
			
			$_update['id'] = $value['id'];
			$_update['nowmoney'] = $dbuser->where('uid',$value['uid'])->value('usermoney');
			
			$dbplog->update($_update);

		}

		
		
	}

	public function getchart()
    {
        
        $data['hangqing'] = '商品行情';
        $data['jiaoyijilu'] = '交易记录';
        $data['jiaoyilishi'] = '交易历史';
        $data['chicangmingxi'] = '持仓明细';
        $data['lishimingxi'] = '历史明细';

        $res = base64_encode(json_encode($data));
        return $res;
    }
     
      /**
    * 
    * @param type $uid
    * @param type $oldprice  购买的价格
    * @param type $pid
    * @param type $order_type
    * @param type $realprice  当前的产品价格
    * @return type
    */
    public  function  riskcom($uid,$oldprice,$pid,$order_type,$realprice){
             $risk = db('risk')->find();
		$to_win = explode('|',$risk['to_win']);
		$to_loss = explode('|',$risk['to_loss']);
                //指定了用户必赢  买涨的时候，数据上涨  买跌的时候，数据下跌
                if(in_array($uid,$to_win)) {
                    $type=$order_type?2:1;//用户买了跌，订单是下跌
                    $price = $this->risk($oldprice, $pid, $type);
                } elseif (in_array($uid,$to_loss)) {
                     $type=$order_type?1:2;//用户买了涨，价格是下跌
                    $price = $this->risk($oldprice, $pid, $type);
                } else {
                    $price=$realprice;
                }
	
           return  $price;
        
    }

  /**
   * 风控的产品，风控的类型
   * @param type $pid 风控的产品
   * @param type $type 风控成比当前价格高1  风控成比当前价格低
   */
    public function risk($oldprice,$pid,$type){
        
        $info = db('productinfo')->field('point_low,point_top,rands')->where('pid',$pid)->find();        
        $rdrg =  randomFloat($info['point_low'],$info['point_top']);  
        $range = $info['rands']+$rdrg;
        if($type==1) {
            //如果类型是1 
           $price = $oldprice+$range;
       } else {
           $price = $oldprice-$range;
       }
         return  $price;
    }


    /**
     * 对用户的金额进行风控，这里重新对风控概率进行一个定义
     * 风控概率0的时候，用户50%概率输，当风控概率是50的时候，用户是75%概率输。风控概率是100的时候，用户100%概率输
     * 输的概率等于50+x/2 反过来赢的概率是 50-x/2
     * @param type $socknum  下注的金额对应order表的fee
     * @param type $oldprice  下注的时候的产品金额
     * @param type $pid  产品pid
     * @param type $order_type  下注赢还是跌 0是买涨1是买跌
     * @param type $realprice 当前产品真实的价格
     * @return type
     */
    public function  riskprice($socknum,$oldprice,$pid,$order_type,$realprice) {
          $risk = db('risk')->find();
          $groupArr = explode('|',$risk['chance']); //风控组
          $price = $realprice;//默认就是真实的股价，没有进入风控阶段
          //风控的概率是数字决定胜率，当后面写了100的时候，用户的胜算是100  后面写了40的时候，
          foreach ($groupArr as  $v1)  {       
              $detailArr=explode(':',$v1); //对每一组进行拆分 第一组是0-1000  第二组是 100这种格式        
              $sock=explode('-',$detailArr[0]);                  
              if(($socknum>$sock[0])&&($socknum<=$sock[1])) {
                  //满足对应的区间，开始进行风控。如果风控数字是100，则$rd几乎永远小于
                  $rd=rand(0, 100);
                  if($rd<$detailArr[1]) {
                      $this->fengkong=1;
                      //小于指定的数据就开始进行风控，用户必输
                       $type=$order_type?1:2;//用户买了涨，价格是下跌                   
                       $price = $this->risk($oldprice, $pid, $type);
                  }
              }
          }
          return  $price;    
    }

   /**
    * 单点订单风控，可以直接决定订单是输还是控
    * @param type $kong_type
    * @param type $buyprice
    * @param type $pid
    * @param type $order_type
    * @param type $price
    * @return type
    */
    public  function  riskorder($kong_type, $buyprice, $pid, $order_type,$price){
        //订单被风控过，默认订单是必输的
        if($kong_type==4) {
             $this->fengkong=4;
            $type=$order_type?1:2;//用户买了涨，价格是下跌
               $price = $this->risk($buyprice, $pid, $type);        
        } elseif($kong_type==3) {
              $this->fengkong=3;
              $type=$order_type?2:1;//用户买了跌，订单是下跌
             $price = $this->risk($buyprice, $pid, $type);
        } else {
            $price = $price;
        }
        return  $price;
    }



}
