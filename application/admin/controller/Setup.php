<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;

/**
 * 系统设置和积分比例设置，可自定义设置
 */
class Setup extends Base
{

    /**
     * 基本设置
     * @return [type] [description]
     * @author lukui  2017-04-19
     */
    public function index()
    {
        return $this->fetch();
    }

    public function index1()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }

        $map['group'] = 1;
        $map['status'] = 1;
        $data = Db::name('config')->where($map)->order('sort asc')->select();
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 比例设置
     * @return [type] [description]
     * @author lukui  2017-04-19
     */
    public function proportion()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }

        $map['group'] = 2;
        $map['status'] = 1;
        $data = Db::name('config')->where($map)->order('sort asc')->select();
        $this->assign('data', $data);
        return $this->fetch('proportion');
    }

    public function about()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }
        $map['id'] = 50;
        $map['status'] = 1;
        $data = Db::name('config')->where($map)->order('sort asc')->find();
        $this->assign('data', $data);
        return $this->fetch('about');
    }


    /**
     * 配置比例
     * @return [type] [description]
     * @author lukui  2017-04-19
     */
    public function addsetup()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }

        if (input('post.')) {
            $data = input('post.');
            $data['create_time'] = $data['update_time'] = time();
            $data['status'] = 1;
            if (isset($data['id'])) {
                $ids = Db::name('config')->update($data);
            } else {
                $ids = Db::name('config')->insert($data);
            }

            if ($ids) {
                cache('conf', null);
                return WPreturn('配置成功', 1);
            } else {
                return WPreturn('配置失败，请重试', -1);
            }

            exit;
        } else {

            if (input('param.id')) {
                $id = input('param.id');
                $data = Db::name('config')->where('id', $id)->find();
                $this->assign($data);
            }
            return $this->fetch();
        }
    }


    /**
     * 编辑配置/比例
     * @return [type] [description]
     * @author lukui  2017-04-19
     */

    public function getConfig()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }
        $get = input('get.');
        $where['group'] = $get['group'];
        $data = Db::name('config')->field('id, type, status, name, title, value, extra')->where($where)->select();
        if (!empty($data)) {
            return json_encode($data, JSON_FORCE_OBJECT);
        } else {
            return 1;
        }
    }

    /**
     *  会员等级
     */
    public function memlevel(){
        return $this->fetch();
    }

    /**
     *  获取会员等级
     */
    public function MemLevelInit(){
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
        return json_encode($memberLevel, JSON_UNESCAPED_UNICODE);
    }

    /**
     *  添加会员等级
     */
    public function addMemLevel(){
        $memberLevel = cache('memberLevel');
        $count = count($memberLevel);
        $memberLevel[$count] = ['name' => 'vip会员', 'src'=>''];
        cache('memberLevel', $memberLevel);
    }

    /**
     * 修改会员等级
     */
    public function editMemLevel(){
        if(input('post.')){
            $post = input('post.');
            foreach ($post as $key=>$val){
                if($val['name'] != ''){
                    $name = strip_tags(htmlspecialchars_decode($val['name']));
                    if(!$name){return -1;}
                    $val['name'] = trim($name);
                    $post[$key] = $val;
                }
            }
            cache('memberLevel', $post);
            return 1;
        }else{
            return -1;
        }
    }

    /**
     * 删除会员等级
     */
    public function dropMemLevel(){
        $memberLevel = cache('memberLevel');
        if(input('post.')) {
            $post = input('post.');
            unset($memberLevel[$post['index']]);
            cache('memberLevel', $memberLevel);
            return 1;
        }else{
            return -1;
        }
    }

    public function updateConf()
    {
        if ($this->otype != 3) {
            exit('出错了!');
        }
        if (input('post.')) {
            $data = input('post.');
            $get = input('get.');
            $where['group'] = $get['group'];
            $where['status'] = 1;
            $config = Db::name('config')->field('id, name, value')->where($where)->select();
            foreach ($config as $val) {
                if(isset($data[$val['name']])){
                    $where1['id'] = $val['id'];
                    $val['value'] = $data[$val['name']];
                    Db::name('config')->where($where1)->update($val);
                }
            }
            cache('conf', null);
            return 1;
        }
    }

    public function updateBannerImg()
    {
        if (input('post.')) {
            $data = input('post.');
            $where['id'] = $data['id'];
            $params['value'] = $data['bannerimg2'];
            $ids = Db::name('config')->where($where)->update($params);
            if($ids){
                return 1;
            }else{
                return -1;
            }
        }
    }

    public function openCon(){
        if (input('post.')) {
            $data = input('post.');
            $get = input('get.');
            $where['group'] = $get['group'];
            $config = Db::name('config')->field('id')->where($where)->select();
            foreach ($config as $val) {
                $where1['id'] = $val['id'];
                if(!in_array($val['id'], $data['data'])){
                    $_data['status'] = 0;
                }else{
                    $_data['status'] = 1;
                }
                Db::name('config')->where($where1)->update($_data);
            }
            cache('conf', null);
            return 1;
        }
    }

    public function upload()
    {
        return eleUpload();
    }

    public function editconf()
    {

        //echo '出错了!';exit;
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }

        if (input('post.')) {
            $data = input('post.');
            foreach ($data as $k => $v) {
                $arr = explode('_', $k);
                $_data['id'] = $arr[1];
                $_data['value'] = $v;
                $file = request()->file('pic_' . $_data['id']);
                if ($file) {
                    $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
                    if ($info) {
                        $_data['value'] = '/public' . DS . 'uploads/' . $info->getSaveName();
                    }
                }
                if ($_data['value'] == '' && isset($arr[2]) && $arr[2] == 3) {
                    continue;
                }
                Db::name('config')->update($_data);
            }
            cache('conf', null);
            $this->success('编辑成功');
        }
    }

    /**
     *  配置类型4，是否显示保存
     */
    public function isShowKeep(){
        $post = input('post.');
        $data['value'] = $post['value'];
        $ids = Db::name('config')->where('id', $post['id'])->update($data);
        if($ids){
            return 1;
        }else{
            return -1;
        }
    }

    public function editgywm()
    {
        $data = input('post.');
        Db::name('config')->where('id', $data['id'])->update($data);
        cache('conf', null);
        $this->success('编辑成功');
    }


    public function deleteconf()
    {

        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }

        if (input('post.')) {

            $id = input('post.id');

            if (!$id) {
                return WPreturn('参数错误', -1);
            }

            $_data['id'] = $id;
            $_data['status'] = 0;

            $ids = Db::name('config')->update($_data);
            if ($ids) {
                cache('conf', null);
                return WPreturn('删除成功', 1);
            } else {
                return WPreturn('删除失败，请重试', -1);
            }

        }
    }


    /**
     * 所有配置列表
     * @return [type] [description]
     * @author lukui  2017-04-19
     */
    public function deploy()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }
        $map['status'] = 1;
        $config = Db::name('config')->where($map)->order('sort asc')->select();
        $data = [];
        foreach ($config as $key=>$val){
            if($val['type'] == 1){
                $val['type'] = '字符';
            }elseif($val['type'] == 2){
                $val['type'] = '文本';
            }elseif($val['type'] == 3){
                $val['type'] = '图片';
            }elseif($val['type'] == 4){
                $val['type'] = '开关';
            }
            $data[$key] = $val;
        }
        $this->assign('data', $data);
        return $this->fetch();
    }
}
