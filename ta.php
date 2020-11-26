<?php

namespace console\models;

use Yii;
use common\models\Order;
use common\models\Product;
use common\models\DataAll;
use common\models\ProductParam;
use common\helpers\StringHelper;
use common\helpers\Curl;

class GatherSina extends Gather
{
    public $urlPrefix = 'http://hq.sinajs.cn/list=';
    // 交易产品列表，格式为["表名" => "抓取链接参数名"]
    public $productList = [
        'btc'=>'btc_btcbitstamp',
    ];

    public function run()
    {
        // echo 1111;exit;
        $this->switchMap = option('risk_product') ?: [];

        $products = Product::find()->where(['state' => 1, 'on_sale' => 1, 'source' => 1])->select('table_name, code, trade_time, id')->asArray()->all();

        // var_dump(json_encode($products));

        // exit();
        //  $products[8]['code']='USDCHF';
        // $products[9]['code']='EURUSD';

        $this->productList = array_merge($this->productList, $products);
        print_r($this->productList);
        // exit();

        foreach ($this->productList as $tableName => $info) {


            // var_dump(json_encode($info));
            // var_dump($info['code']);
            // exit();
            if (is_int($tableName)) {
              if($info)
                $large = array("sh000001", "sz399001", "sz399006");

                $start = strtotime(date('Y-m-d 00:00:00', time()));
                if ($info['trade_time'] && $info['code'] != 'fx_sgbpusd') {
                    $timeArr = unserialize($info['trade_time']);
                    $start = strtotime(date('Y-m-d ' . $timeArr[0]['start'] . ':00'));
                    $time = end($timeArr);
                    $end = strtotime(date('Y-m-d ' . $time['end'] . ':00'));
                    if ($start > $end) {
                        if ($start > time() && $end < time()) {
                            continue;
                        }
                    } else {
                        if ($start > time() || $end < time()) {
                            continue;
                        }
                    }
                }

                // $url = 'http://hq.sinajs.cn/etag.php?_='.time().'1000&list='.$info['table_name'];

                // if(in_array($info['table_name'], $large)) {
                //     $url = 'http://hq.sinajs.cn/etag.php?_='.time().'1000&list='.$info['table_name'];
                // } else {
                //     $url = 'http://hq.sinajs.cn/list='.$info['table_name'];
                // }

                $url = 'http://hq.sinajs.cn/etag.php?_='.time().'1000&list='.$info['code'];

                $result = $this->getHtml($url);

                if ($result) {

                    $resultarr = explode(',', $result);
                    if(sizeof($resultarr) < 3) {
                        break;
                    }

                    if(in_array($info['code'], array('hf_CL', 'hf_GC', 'hf_HSI', 'hf_NG', 'hf_CAD', 'hf_SI', 'CU0','USDCHF','EURUSD'))) {
                        $price = explode('"', $resultarr[0])[1];
                        $diff = $price - $resultarr[7];
                        if($diff == 0) {
                            $diff_rate = 0.00;
                        } else {
                            $diff_rate = $resultarr[1];
                        }

                        $data = [
                            'price' => $price,
                            'open' => $resultarr[8],
                            'high' => $resultarr[4],
                            'low' => $resultarr[5],
                            'close' => $resultarr[7],
                            'diff' => $diff,
                            'diff_rate' => $diff_rate,
                            'time' => $resultarr[12]." " .$resultarr[6]
                        ];

                        // var_dump(json_encode($data));
                    } elseif($info['code'] == 'hkHSI') {
                        $price = $resultarr[6];
                        $diff = $resultarr[7];
                        $diff_rate = $resultarr[8];
                        // echo $resultarr[sizeof($resultarr) - 2] ." " .explode('"', $resultarr[sizeof($resultarr) - 1])[0];
                        $dtime = strtotime($resultarr[sizeof($resultarr) - 2] ." " .explode('"', $resultarr[sizeof($resultarr) - 1])[0]);
                        // echo date('Y-m-d H:i:s', $dtime);
                        $data = [
                            'price' => $price,
                            'open' => $resultarr[2],
                            'high' => $resultarr[4],
                            'low' => $resultarr[5],
                            'close' => $resultarr[3],
                            'diff' => $diff,
                            'diff_rate' => $diff_rate,
                            'time' => date('Y-m-d H:i:s', $dtime)
                        ];
                    } elseif(in_array($info['code'], array('fx_seurusd', 'fx_sgbpusd', 'fx_saudusd', 'fx_scadusd'))) {
                        $price = $resultarr[1];
                        $diff = $price - $resultarr[3];
                        if($diff == 0) {
                            $diff_rate = 0.00;
                        } else {
                            $diff_rate = number_format($diff / $resultarr[3] * 100, 2, ".", "");
                        }
                        // echo $resultarr[sizeof($resultarr) - 2] ." " .explode('"', $resultarr[sizeof($resultarr) - 1])[0];
                        $dtime = strtotime(explode('"', $resultarr[sizeof($resultarr) - 1])[0]." " .explode('"', $resultarr[0])[1]);
                        // echo date('Y-m-d H:i:s', $dtime);
                        $data = [
                            'price' => $price,
                            'open' => $resultarr[5],
                            'high' => $resultarr[6],
                            'low' => $resultarr[8],
                            'close' => $resultarr[3],
                            'diff' => $diff,
                            'diff_rate' => $diff_rate,
                            'time' => date('Y-m-d H:i:s', $dtime)
                        ];
                    }  else {
                        // $diff = $resultarr[0] - $resultarr[7];
                        // if($diff == 0) {
                        //     $diff_rate = 0.00;
                        // } else {
                        //     $diff_rate = number_format(($resultarr[3] - $resultarr[2]) / $resultarr[2] * 100, 2, ".", "");
                        // }
                        // $data = [
                        //     'price' => $resultarr[3],
                        //     'open' => $resultarr[1],
                        //     'high' => $resultarr[4],
                        //     'low' => $resultarr[5],
                        //     'close' => $resultarr[2],
                        //     'diff' => $diff,
                        //     'diff_rate' => $diff_rate,
                        //     'time' => date('Y-m-d H:i:s', strtotime($resultarr[sizeof($resultarr) - 3] ." " .$resultarr[sizeof($resultarr) - 2]))
                        // ];
                    }

                    // if($info['table_name']=='usdchf')print_r($data);
                    $this->insert($info['table_name'], $data);
                }

            } else {
                // 每个品类，先采集最新价格

                if($info['typeprefix'] == 'etag') {
                    $info['url'] = str_replace("{time}", time(), $info['url']).$tableName;
                }

                $result = $this->getHtml($info['url']);
                if ($result) {
                    $resultarr = explode(',', $result);
                    if($info['typeprefix'] == 'etag') {
                        $info = [
                            'price' => $resultarr[3],
                            'open' => $resultarr[1],
                            'high' => $resultarr[4],
                            'low' => $resultarr[5],
                            'close' => $resultarr[2],
                            'diff' => $resultarr[3] - $resultarr[2],
                            'diff_rate' => ($resultarr[3] - $resultarr[2]) / $resultarr[2],
                            'time' => date('Y-m-d H:i:s', strtotime($resultarr[sizeof($resultarr) - 3] ." " .$resultarr[sizeof($resultarr) - 2]))
                        ];
                    } else {
                        $info = [
                            'price' => $resultarr[1],
                            'open' => $resultarr[1],
                            'high' => $resultarr[1],
                            'low' => $resultarr[1],
                            'close' => $resultarr[1],
                            'diff' => $resultarr[2],
                            'diff_rate' => $resultarr[3],
                            'time' => date('Y-m-d H:i:s', time())
                        ];
                    }


                    $this->insert($tableName, $info);
                }
            }
        }
        // 更新 data_all 的最新价格
        // foreach ($this->updateMap as $key => $value) {
        //     $value['diff'] = sprintf('%.2f', $value['diff']);
        //     self::dbUpdate('data_all', ['price' => $value['price'], 'time' => $value['time'], 'diff' => $value['diff'], 'diff_rate' => $value['diff_rate']], ['name' => $key]);
        // }
        // 监听是否有人应该平仓
        $this->listen();
    }

    protected function getHtml($url, $options = null)
    {
        $options[CURLOPT_HTTPHEADER] = ['Referer: http://hq.sinajs.cn'];
        return Curl::get($url, $options);
    }
}
