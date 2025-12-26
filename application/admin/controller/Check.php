<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

class Check  extends Backend
{
    public function index()
    {
        $start = time() - 86400;
//        echo "1.开始检查数据1.CNY提现检测" . PHP_EOL;
        $money = Db::name('keerta_withdraw_cny')
            ->whereIn('status',[1,2])
            ->where('actual_time','>', $start)
            ->select();
        $data = [];
        if($money){
            foreach ($money as $v){
                if($v['status'] == 1){
                    $count = Db::name('user_money_log')->where('order_sn', $v['order_sn'])
                        ->where('memo','提现')
                        ->count();
                    if($count > 1){
//                        echo "检测CNY提现数据出现异常提现ID：" .$v['id']. " 订单号：" . $v['order_sn'] . "用户ID：" . $v['user_id']. PHP_EOL;
                        $data[] =  $this->buildData($v, "检测CNY提现数据出现异常提现ID：" .$v['id']. " 订单号：" . $v['order_sn'] . "用户ID：" . $v['user_id']);
                    }
                }else{
                    $count = Db::name('user_money_log')->where('order_sn', $v['order_sn'])
                        ->where('memo','提现失败退回')
                        ->count();
                    if($count > 1){
//                        echo "检测CNY提现失败退回数据出现异常提现ID：" .$v['id']. " 订单号：" . $v['order_sn'] . "用户ID：" . $v['user_id']. PHP_EOL;
                        $data[] = $this->buildData($v, "检测CNY提现失败退回数据出现异常提现ID：" .$v['id']. " 订单号：" . $v['order_sn'] . "用户ID：" . $v['user_id']);
                    }
                }
            }
        }else{
//            echo "CNY提现没有需要检测的数据" . PHP_EOL;
        }
//        echo '==================================================================='. PHP_EOL;
//        echo "2.开始检查数据1.USDT提现检测" . PHP_EOL;
        $money = Db::name('keerta_withdraw_usdt')
            ->whereIn('status',[1,2])
            ->where('actual_time','>', $start)
            ->select();
        $data = [];
        if($money){
            foreach ($money as $v){
                if($v['status'] == 1){
                    $count = Db::name('user_money_log')->where('order_sn', $v['order_sn'])
                        ->where('memo','提现')
                        ->count();
                    if($count > 1){
//                        echo "检测USDT提现数据出现异常提现ID：" .$v['id']. " 订单号：" . $v['order_sn'] . "用户ID：" . $v['user_id']. PHP_EOL;
                        $data[] =  $this->buildData($v, "检测USDT提现数据出现异常提现ID：" .$v['id']. " 订单号：" . $v['order_sn'] . "用户ID：" . $v['user_id']);
                    }
                }else{
                    $count = Db::name('user_money_log')->where('order_sn', $v['order_sn'])
                        ->where('memo','提现失败退回')
                        ->count();
                    if($count > 1){
//                        echo "检测USDT提现失败退回数据出现异常提现ID：" .$v['id']. " 订单号：" . $v['order_sn'] . "用户ID：" . $v['user_id']. PHP_EOL;
                        $data[] = $this->buildData($v, "检测USDT提现失败退回数据出现异常提现ID：" .$v['id']. " 订单号：" . $v['order_sn'] . "用户ID：" . $v['user_id']);
                    }
                }
            }
        }else{
//            echo "USDT提现没有需要检测的数据" . PHP_EOL;
        }
//        echo '==================================================================='. PHP_EOL;

//        echo "3.开始检查数据2.CNY充值检测" . PHP_EOL;
        $money = Db::name('keerta_money')
            ->where('status',1)
            ->where('updatetime','>', $start)
            ->select();
        if ($money){
            foreach ($money as $v){
                $count = Db::name('user_money_log')->where('order_sn', $v['order_sn'])
                    ->where('memo','充值')
                    ->count();
                if($count > 1){
//                    echo "检测CNY充值数据出现异常充值ID：" .$v['id']. " 订单号：" . $v['order_sn'] . "用户ID：" . $v['user_id']. PHP_EOL;
                    $data[] = $this->buildData($v, "检测CNY充值数据出现异常充值ID：" .$v['id']. " 订单号：" . $v['order_sn'] . "用户ID：" . $v['user_id']);
                }
            }
        }else{
//            echo "CNY充值没有需要检测的数据" . PHP_EOL;
        }
//        echo '==================================================================='. PHP_EOL;
//        echo "4.开始检查数据2.USDT充值检测" . PHP_EOL;
        $money = Db::name('keerta_usdt')
            ->where('status',1)
            ->where('updatetime','>', $start)
            ->select();
        if ($money){
            foreach ($money as $v){
                $count = Db::name('user_money_log')->where('order_sn', $v['order_sn'])
                    ->where('memo','充值')
                    ->count();
                if($count > 1){
//                    echo "检测USDT充值数据出现异常充值ID：" .$v['id']. " 订单号：" . $v['order_sn'] . "用户ID：" . $v['user_id']. PHP_EOL;
                    $data[] = $this->buildData($v, "检测USDT充值数据出现异常充值ID：" .$v['id']. " 订单号：" . $v['order_sn'] . "用户ID：" . $v['user_id']);
                }
            }
        }else{
//            echo "USDT充值没有需要检测的数据" . PHP_EOL;
        }
        if($data){
            print_r($data);
        }else{
            echo "没有检测到异常的数据" . PHP_EOL;
        }
//        echo '==================================================================='. PHP_EOL;
        return $this->view->fetch();
    }

    /**
     * 数据组装
     *
     */
    private function buildData($v, $memo)
    {
        return [
            'id' => $v['id'],
            'user_id' => $v['user_id'] ?? $v['id'],
            'order_sn' => $v['order_sn'] ?? '',
            'memo' => $memo,
        ];
    }
}