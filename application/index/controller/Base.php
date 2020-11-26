<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Loader;

class Base extends Controller
{
    public function __construct(){
		parent::__construct();
		session(['prefix' => '', 'expire' => 60*60*24]);
		$this->token = md5(time());
		$this->assign('token',$this->token);
//
//		//推荐
		$fid = input('get.fid');
		if($fid){
			$_SESSION['fid'] = $fid;
			if(!isset($_SESSION['uid'])){
				$this->redirect('login/register?token='.$this->token);
			}
		}

		//session_unset();
		//验证登录

		if(!isset($_SESSION['uid'])){
			//$this->error('请先登录！','index.php/index/user/login',1,1);
			$this->redirect('login/login?token='.$this->token);
		}
		

		$this->uid = $_SESSION['uid'];
		$this->user = db('userinfo')->where('uid',$this->uid)->find();

		if(!$this->user){
			unset($_SESSION['uid']);
			$this->redirect('login/login?token='.$this->token);
		}


		$this->assign('userinfo',$this->user);
		//网站配置信息
		$this->conf = getConf1();
        //获取地域名
        if(!cache('province')){
            $province = db('area')->where(array('pid' => 0))->select();
            cache('province',$province);
        }
        if(!cache('banks')){
            $banks = Db::name('banks')->select();
            cache('banks',$banks);
        }
        $this->assign('province', cache('province'));
        $this->assign('banks', cache('banks'));
		if($this->conf['is_close'] != 1){
            header('Location:/error.html');
            exit;
        }
		$this->assign('conf',$this->conf);
        if(empty($this->user['utel']) || empty($this->user['upwd'])){
            $this->redirect('login/addpwd?token='.$this->token);
        }
		/*if(empty($this->user['oid']) || empty($this->user['utel']) || empty($this->user['upwd'])){
			$this->redirect('login/addpwd?token='.$this->token);
		}*/


		//test
		//dump($_SESSION);exit;

	}

	protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
    	$replace['__HOME__'] = str_replace('/index.php','',\think\Request::instance()->root()).'/static/index';
        return $this->view->fetch($template, $vars, $replace, $config);
    }
}
