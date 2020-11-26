<?php
namespace app\Tz\controller;
use think\Db;
use think\Cookie;
 header("Access-Control-Allow-Origin:*");


class Tz extends Base
{

	public function tx() {
	   header("Access-Control-Allow-Origin:*");
	   $map = "null";
	   $pz = Db::name('balance')->where('isverified', $map)->count();
	   return $pz;
   }

    public function xs() {
	    // $ids = db('order')->->update($data);
		$rq = date('Y-m-d');
		 
        $map = "已查看";

        $xs = Db::name('order')->where('xs', $map)->where('buytime', '> time', $rq)->count();
        return $xs;
        // var_dump($xs);
   }
    
}


   
    
 
 