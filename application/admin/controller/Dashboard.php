<?php

namespace app\admin\controller;

use app\admin\controller\keerta\order\Order;
use app\admin\model\Admin;
use app\admin\model\keerta\Money;
use app\admin\model\keerta\recharge\Usdt;
use app\admin\model\keerta\withdraw\Cny;
use app\admin\model\User;
use app\common\controller\Backend;
use app\common\model\Attachment;
use fast\Date;
use think\Db;

/**
 * 控制台
 *
 * @icon   fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看202512
     */
    public function index()
    {
        try {
            \think\Db::execute("SET @@sql_mode='';");
        } catch (\Exception $e) {

        }
        $column = [];
        $starttime = Date::unixtime('day', -6);
        $endtime = Date::unixtime('day', 0, 'end');
        $joinlist = Db("user")->where('jointime', 'between time', [$starttime, $endtime])
            ->field('jointime, status, COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(jointime), "%Y-%m-%d") AS join_date')
            ->group('join_date')
            ->select();
        for ($time = $starttime; $time <= $endtime;) {
            $column[] = date("Y-m-d", $time);
            $time += 86400;
        }
        $userlist = array_fill_keys($column, 0);
        foreach ($joinlist as $k => $v) {
            $userlist[$v['join_date']] = $v['nums'];
        }

        $dbTableList = Db::query("SHOW TABLE STATUS");
        $addonList = get_addon_list();
        $totalworkingaddon = 0;
        $totaladdon = count($addonList);
        foreach ($addonList as $index => $item) {
            if ($item['state']) {
                $totalworkingaddon += 1;
            }
        }
        $mark = \app\common\model\User::where('mark', 1)->column('id');
        $this->view->assign([
            'totaluser'         => User::count(),
            'totaladdon'        => $totaladdon,
            'totaladmin'        => Admin::count(),
            'totalcategory'     => \app\common\model\Category::count(),
            'todayusersignup'   => User::whereTime('jointime', 'today')->count(),
            'todayuserlogin'    => User::whereTime('logintime', 'today')->count(),
            'sevendau'          => User::whereTime('jointime|logintime|prevtime', '-7 days')->count(),
            'thirtydau'         => User::whereTime('jointime|logintime|prevtime', '-30 days')->count(),
            'threednu'          => User::whereTime('jointime', '-3 days')->count(),
            'sevendnu'          => User::whereTime('jointime', '-7 days')->count(),
            'dbtablenums'       => count($dbTableList),
            'dbsize'            => array_sum(array_map(function ($item) {
                return $item['Data_length'] + $item['Index_length'];
            }, $dbTableList)),
            'totalworkingaddon' => $totalworkingaddon,
            'attachmentnums'    => Attachment::count(),
            'attachmentsize'    => Attachment::sum('filesize'),
            'picturenums'       => Attachment::where('mimetype', 'like', 'image/%')->count(),
            'picturesize'       => Attachment::where('mimetype', 'like', 'image/%')->sum('filesize'),
            'total_recharge_money' => round(Money::where('status', 1)->whereNotIn('user_id', $mark)->sum('money'), 2),
            'today_recharge_money' => round(Money::where('status', 1)->whereNotIn('user_id', $mark)->whereTime('createtime', 'today')->sum('money'), 2),
            'total_recharge_usdt' => round(Usdt::where('status', 1)->whereNotIn('user_id', $mark)->sum('money'), 2),
            'today_recharge_usdt' => round(Usdt::where('status', 1)->whereNotIn('user_id', $mark)->whereTime('createtime', 'today')->sum('money'), 2),
            'total_order_amount' => round(\app\admin\model\keerta\order\Order::whereNotIn('user_id', $mark)->sum('amount'), 2),
            'today_order_amount' => round(\app\admin\model\keerta\order\Order::whereTime('createtime', 'today')->whereNotIn('user_id', $mark)->sum('amount'), 2),
            'total_first_money' => round(Money::where('status', 1)->where('is_first', 1)->whereNotIn('user_id', $mark)->whereTime('createtime', 'today')->sum('money'), 2),
            'today_first_count' => Money::where('status', 1)->where('is_first', 1)->whereNotIn('user_id', $mark)->whereTime('createtime', 'today')->count(),
            'total_first_usdt' => round(Usdt::where('status', 1)->where('is_first', 1)->whereNotIn('user_id', $mark)->whereTime('createtime', 'today')->sum('money'),2),
            'today_first_usdt_count' => Usdt::where('status', 1)->where('is_first', 1)->whereNotIn('user_id', $mark)->whereTime('createtime', 'today')->count(),
            'total_withdraw_money' => round(Cny::where('status', 1)->whereNotIn('user_id', $mark)->sum('money'), 2),
            'today_withdraw_money' => round(Cny::where('status', 1)->whereNotIn('user_id', $mark)->whereTime('createtime', 'today')->sum('money'),2),
            'total_withdraw_usdt' => round(\app\admin\model\keerta\withdraw\Usdt::where('status', 1)->whereNotIn('user_id', $mark)->sum('money'), 2),
            'today_withdraw_usdt' => round(\app\admin\model\keerta\withdraw\Usdt::where('status', 1)->whereNotIn('user_id', $mark)->whereTime('createtime', 'today')->sum('money'), 2),
        ]);

        $this->assignconfig('column', array_keys($userlist));
        $this->assignconfig('userdata', array_values($userlist));

        return $this->view->fetch();
    }

}
