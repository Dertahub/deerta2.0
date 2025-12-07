<?php

namespace app\api\controller\xy;

use app\admin\model\Signin;
use app\common\controller\Api;
use app\common\model\MoneyLog;
use think\Cache;
use think\Db;

class Sign extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 签到
     *
     */
    public function doSign()
    {
        $userId = $this->auth->id;
        $user = $this->auth->getUser();

        if($user['realname_status'] != 2){
            $this->error('请先完成实名认证！');
        }
        $realname = \app\admin\model\keerta\Realname::where('user_id', $user['id'])->find();
        if(!$realname){
            $this->error('请先完成实名认证。');
        }
        if($realname['status'] != 1){
            $this->error('请先完成实名认证！');
        }

        $date = date('Ymd');
        $cacheKey = 'user_sign_'.$date.'_'.$userId;
        $cache = Cache::get($cacheKey);
        if ($cache) {
            $this->error('今天已经签到过了!!');
        }
        $sign = Signin::where('user_id', $userId)
            ->whereTime('createtime', 'today')
            ->find();
        if ($sign) {
            $this->error('今天已经签到过了！');
        }
        Db::startTrans();
        try {
            $user = $this->auth->getUser();
            $amount = \app\admin\model\keerta\level\Level::where('id', $user['level_id'])->value('sign_reward') ?? 0;
            Signin::create([
                'user_id' => $userId,
                'createtime' => time(),
            ]);
            if($amount > 0){
                $order_sn = \fast\Random::uuid();
                MoneyLog::money($userId, $amount, 3, 'withdraw_money', $order_sn);
            }

            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }

        Cache::set($cacheKey, 1, 86400);
        Cache::rm('user_sign_week_'.$userId);
        $this->success('签到成功！', [
            'amount' => $amount
        ]);
    }

    /**
     * 获取本周签到情况
     *
     */
    public function getWeekSign()
    {
        $userId = $this->auth->id;

        // 获取本周的开始和结束时间戳（周一到周日）
        $startOfWeek = strtotime('monday this week');
        $endOfWeek = strtotime('sunday this week 23:59:59');

        // 生成缓存键，使用周范围而不是具体日期
        $cacheKey = 'user_sign_week_'.$userId;
        $cache = Cache::get($cacheKey);

        if ($cache) {
            $this->success('获取成功！', $cache);
        }

        // 查询本周的所有签到记录
        $weekSign = Signin::where('user_id', $userId)
            ->whereBetween('createtime', [$startOfWeek, $endOfWeek])
            ->select();

        $weekSign = collection($weekSign)->toArray();

        // 提取已签到的日期（格式：m/d）
        $signedDates = array_map(function ($item) {
            return date('m/d', $item['createtime']);
        }, $weekSign);

        // 创建本周所有日期的数组
        $weekDates = [];
        $currentDate = $startOfWeek;

        $today = date('m/d');
        $todaySign = 0;
        while ($currentDate <= $endOfWeek) {
            $dateKey = date('m/d', $currentDate);
            $weekDates[] = [
                'date' => $dateKey,
                'isSign' => in_array($dateKey, $signedDates) ? 1 : 0
            ];
            if ($dateKey == $today) {
                $todaySign = in_array($dateKey, $signedDates) ? 1 : 0;
            }
            $currentDate = strtotime('+1 day', $currentDate);
        }

        $data = [
            'todaySign'=>$todaySign,
            'weekDates'=>$weekDates
        ];
        Cache::set($cacheKey, $data, 60);

        $this->success('获取成功！', $data);
    }

    /**
     * 签到红包记录
     *
     */
    public function signLog()
    {
        $limit = $this->request->param('limit', 10, 'int');
        $userId = $this->auth->id;
        $list = MoneyLog::where('user_id', $userId)
            ->where('type', 3)
            ->field('id,money,createtime,memo')
            ->order('id desc')
            ->paginate($limit);

        $this->success('获取成功！', ['list' => $list]);
    }

}