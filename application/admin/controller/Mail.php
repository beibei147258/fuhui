<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;

class Mail extends Base
{
	

	/**
	 * 私信列表
	 * @author lukui  2017-02-13
	 * @return [type] [description]
	 */
    public function maillist(){
    	$where ="";
		$pagenum = cache('page');
		$state = input('state'); // 获取全部参数
		$uid = input('uid'); // 获取全部参数
		if($state!=""){
			$where['state']=$state;
		}
		if($uid!=""){
			$where['uid']=$uid;
		}
		$mail=Db::name("mail")->where($where)->order('create_time desc')->paginate($pagenum,false,['query'=>request()->param()]);
		 $this->assign('state',$state);//条件
		 $this->assign('uid',$uid);//条件
		 $this->assign('mail',$mail);
        return $this->fetch('maillist');
    }
	//添加
	public function mailadd(){
       if(input('post.')){
			$data = input('post.');

			//去除空字符串，无用字符串
			$data = array_filter($data);
			$data['update_time']=time();
			$data['create_time']=time();
			//插入数据
			$count = Db::name('mail')->insertGetId($data);

			if ($count>0) {
				 $this->success('添加成功','maillist');
			}else{
				 $this->error('添加失败');
			}
		}else{
			$this->assign('lx',1);//1添加2修改
			return $this->fetch();
		}
    }
		//修改
		
	public function mailxg(){
		if(input('post.')){
			$data = input('post.');

			//去除空字符串，无用字符串
			$data = array_filter($data);
			//插入数据
			$count = Db::name('mail')->where('id',$data['id'])->update($data);

			if ($count>0) {
				 $this->success('修改成功','maillist');
			}else{
				 $this->error('修改失败');
			}
		}else{
			$id = input('param.id');
			$mail=Db::name('mail')->where('id',$id)->find();
			$this->assign('lx',2);//1添加2修改
			$this->assign('mail',$mail);
			return $this->fetch('mailadd');
		}
    }
	public function delete()
	{
		
		$id = input('post.id');
		$ids = db('mail')->where('id',$id)->delete();
	}
  


}
