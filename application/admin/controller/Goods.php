<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;

class Goods extends Base
{

    /**
     * 产品列表
     * @return [type] [description]
     * @author lukui  2017-02-15
     */
    public function prolist()
    {
//        if($this->otype != 3){
//            echo 'aa123456!';exit;
//        }
//        $proinfo = Db::name('productinfo')->alias('pi')->field('pi.*,pc.pcname')
//            ->join('__PRODUCTCLASS__ pc','pc.pcid = pi.cid')
//            ->where('pi.isdelete',0)->order('pi.proorder asc')->select();
//
//        $this->assign('proinfo',$proinfo);
//        return $this->fetch();

        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }
        $pro = Db::name('productdata')->alias('d')->field('d.is_deal,i.pid,i.ptitle,i.point,i.point_low,i.point_top,i.proorder')->join('productinfo i', 'd.pid = i.pid', 'LEFT')->where('d.isdelete', 0)->select();
        foreach ($pro as $k => $val) {
            $pro[$k]['pcname'] = "外汇";
            $pro[$k]['isopen'] = $val['is_deal'];
        }
        $this->assign('proinfo', $pro);
        return $this->fetch();
    }

    /**
     * 添加产品
     * @return [type] [description]
     * @author lukui  2017-02-15
     */
    public function proadd()
    {
        if ($this->otype != 3) {
            echo 'error!';
            exit;
        }
        $pid = input('param.pid') ? input('param.pid') : '';
        $this->assign('pid', $pid);
        return $this->fetch();
    }

    public function exitProduct()
    {
        $params = ['type' => -1, 'msg' => '参数错误'];
        if (input('post.')) {
            $data = input('post.');
            $data['cid'] = 5;
            //修改开市时间
            $opentime_arr = [];
            foreach ($data['opentime'] as $key => $val) {
                $opentime_arr[$key] = $val['value'];
            }
            $opentime['opentime'] = implode('-', $opentime_arr);
            $_otime = db('opentime')->where('pid', $data['pid'])->find();
            if ($_otime) {
                db('opentime')->where('pid', $data['pid'])->update($opentime);
            } else {
                $opentime['pid'] = $data['pid'];
                db('opentime')->insert($opentime);
            }
            unset($data['opentime']);
            if (!$data['ptitle'] || !$data['cid']) {
                return json_encode($params, JSON_FORCE_OBJECT);
            }
            $data['time'] = time();
            $data['isdelete'] = 0;
            if (isset($data['pid']) && !empty($data['pid']) && $data['pid'] != 0) { //编辑
                $editid = Db::name('productinfo')->where('pid', $data['pid'])->update($data);
                if ($editid) {
                    $p_data['Name'] = $data['ptitle'];
                    $p_data['Price'] = $data['point'];
                    $p_data['img'] = $data['img'];
                    $where['pid'] = $data['pid'];
                    Db::name('productdata')->where($where)->update($p_data);
                    $params['type'] = 1;
                    $params['msg'] = '修改成功';
                } else {
                    $params['msg'] = '修改失败';
                }
            } else {  //新添加
                $addid = Db::name('productinfo')->insertGetId($data);
                if ($addid) {
                    $p_data['pid'] = $addid;
                    $p_data['Name'] = $data['ptitle'];
                    $p_data['Price'] = $data['point'];
                    $p_data['img'] = $data['img'];
                    Db::name('productdata')->insert($p_data);
                    $params['type'] = 1;
                    $params['msg'] = '添加成功';
                } else {
                    $params['msg'] = '添加失败';
                }
            }
            return json_encode($params, JSON_FORCE_OBJECT);
        } elseif (input('param.pid')) {
            $pid = input('param.pid');
            $productinfo = Db::name('productinfo')->field('pid,ptitle,point,point_low,point_top,procode,proscale,protime,img')->where('pid', $pid)->find();
            $productdata = Db::name('productdata')->field('img')->where('pid', $pid)->find();
            $productinfo['img'] = $productinfo['img'] ? $productinfo['img'] : $productdata['img'];
            $opentime = db('opentime')->where('pid', $pid)->find();
            if ($opentime) {
                $timeArr = explode('-', $opentime["opentime"]);
            } else {
                $timeArr = array('', '', '', '', '', '', '');
            }
            if (count($timeArr) < 7) {
                $count = 7 - count($timeArr);
                for ($i = $count; $i > 0; $i--) {
                    $timeArr[7 - $i] = '';
                }
            } elseif (count($timeArr) > 7) {
                $count = count($timeArr) - 7;
                for ($i = 0; $i < $count; $i++) {
                    unset($timeArr[7 + $i]);
                }
            }
            foreach ($timeArr as $key => $val) {
                $productinfo['opentime'][$key]['value'] = $val;
                if ($key == 0) {
                    $productinfo['opentime'][$key]['name'] = '周一';
                } elseif ($key == 1) {
                    $productinfo['opentime'][$key]['name'] = '周二';
                } elseif ($key == 2) {
                    $productinfo['opentime'][$key]['name'] = '周三';
                } elseif ($key == 3) {
                    $productinfo['opentime'][$key]['name'] = '周四';
                } elseif ($key == 4) {
                    $productinfo['opentime'][$key]['name'] = '周五';
                } elseif ($key == 5) {
                    $productinfo['opentime'][$key]['name'] = '周六';
                } elseif ($key == 6) {
                    $productinfo['opentime'][$key]['name'] = '周日';
                }
            }
            $productinfo = json_encode($productinfo, JSON_FORCE_OBJECT);
            return $productinfo;
        }
    }

    public function upload()
    {
        return eleUpload();
    }

    /**
     * 产品开、休市
     * @return [type] [description]
     * @author lukui  2017-02-15
     */
    public function proisopen()
    {
        // p($_REQUEST);
        $dt['is_deal'] = $_GET['isopen'];
        $r = Db::name('productdata')->where(['pid' => $_GET['pid']])->update(['is_deal' => $dt['is_deal']]);
        header("Location:/admin/goods/prolist.html");
//		if($this->otype != 3){
//			echo '死你全家!';exit;
//		}
//		if (input('post.')) {
//			$data = input('post.');
//			$editid = Db::name('productdata')->update($data);
//
//            if($editid){
//                return WPreturn('修改成功',1);
//            }else{
//                return WPreturn('修改失败',-1);
//            }
//		}else{
//			return WPreturn('参数错误',-1);
//		}
    }

    /**
     * 删除产品
     * @return [type] [description]
     * @author lukui  2017-02-15
     */
    public function delpro()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }
        $id = input('get.id', 0);
        if (!$id) {
            return WPreturn('参数错误', -1);
        }

        $delpro = Db::name('productinfo')->where('pid', $id)->update(['isdelete' => 1]);
        if ($delpro) {
            $p_data['isdelete'] = 1;
            Db::name('productdata')->where('pid', $id)->update($p_data);
            return WPreturn('删除成功', 1);
        } else {
            return WPreturn('删除失败', -1);
        }
    }

    /**
     * 还原产品
     * @return [type] [description]
     * @author lukui  2017-02-15
     */
    public function hypro()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }
        $id = input('get.id', 0);
        if (!$id) {
            return WPreturn('参数错误', -1);
        }

        $delpro = Db::name('productinfo')->where('pid', $id)->update(['isdelete' => 0]);
        if ($delpro) {
            $p_data['isdelete'] = 0;
            Db::name('productdata')->where('pid', $id)->update($p_data);
            return WPreturn('还原成功', 1);
        } else {
            return WPreturn('还原失败', -1);
        }
    }


    /**
     * 产品分类
     * @return [type] [description]
     * @author lukui  2017-02-15
     */
    public function proclass()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }
        $productclass = Db::name('productclass')->where('isdelete', 0)->order('pcid desc')->select();
        $this->assign('productclass', $productclass);
        return $this->fetch();
    }

    /**
     * 编辑、添加产品分类
     * @return [type] [description]
     * @author lukui  2017-02-15
     */
    public function editclass()
    {

        echo '出错了!';
        exit;
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }
        $data['pcid'] = input('post.pcid', 0);
        $data['pcname'] = input('post.pcname', 0);
        $data['isdelete'] = 0;
        if (!$data['pcname']) {
            return WPreturn('参数错误', -1);
        }

        if ($data['pcid']) { //有id 编辑信息
            $editnews = Db::name('productclass')->where('pcid', $data['pcid'])->update(array('pcname' => $data['pcname']));
            if ($editnews) {
                return WPreturn('修改成功', 1);
            } else {
                return WPreturn('修改失败', -1);
            }
        } else { //没di 增加一条
            $addid = Db::name('productclass')->insert($data);
            if ($addid) {
                return WPreturn('添加成功', 1);
            } else {
                return WPreturn('添加失败', -1);
            }
        }
    }

    /**
     * 删除分类
     * @return [type] [description]
     * @author lukui  2017-02-15
     */
    public function deleteclass()
    {

        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }
        $id = input('get.id', 0);
        if (!$id) {
            return WPreturn('参数错误', -1);
        }

        $delpro = Db::name('productclass')->where('pcid', $id)->update(['isdelete' => 1]);
        if ($delpro) {
            return WPreturn('删除成功', 1);
        } else {
            return WPreturn('删除失败', -1);
        }

    }

    /**
     * 风控管理
     * @return [type] [description]
     * @author lukui  2017-02-15
     */
    public function risk()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }

        $risk = Db::name('risk')->find();
        $this->assign($risk);
        return $this->fetch();
    }


    public function addrisk()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }

        $post = input('post.');


        if (!$post) {
            $this->error('禁止访问');
        }

        if (empty($post['id'])) {
            unset($post['id']);
            $ids = db('risk')->insert($post);
        } else {
            $ids = db('risk')->update($post);
        }

        if ($ids) {
            return WPreturn('操作成功', 1);
        } else {
            return WPreturn('操作失败', -1);
        }

    }


    /**
     * 排序
     * @return [type] [description]
     * @author lukui  2017-08-27
     */
    public function proorder()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }

        $post = input('post.');
        if (!isset($post["proorder"])) {
            $this->error('参数错误！');
        }
        $proorder = $post["proorder"];
        $productinfo = db('productinfo');
        foreach ($proorder as $k => $v) {
            $_dara['pid'] = $k;
            $_dara['proorder'] = (int)trim($v);
            $productinfo->update($_dara);

        }

        $this->success('操作成功！');

    }

    public function huishou()
    {
        if ($this->otype != 3) {
            echo '出错了!';
            exit;
        }


        $proinfo = Db::name('productinfo')->alias('pi')->field('pi.*,pc.pcname')
            ->join('__PRODUCTCLASS__ pc', 'pc.pcid = pi.cid')
            ->where('pi.isdelete', 1)->order('pi.pid asc')->select();

        $this->assign('proinfo', $proinfo);
        return $this->fetch();
    }
}
