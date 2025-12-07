<?php

namespace addons\signin\controller\api;

use addons\signin\library\Service;
use addons\signin\model\Signin;
use fast\Date;
use think\Db;
use think\Exception;
use think\exception\PDOException;

class Index extends Base
{

    public function _initialize()
    {
        try {
            \think\Db::execute("SET @@sql_mode='';");
        } catch (\Exception $e) {

        }
        parent::_initialize();
    }

    /**
     * 签到首页
     * @return string
     */
    public function index()
    {
        $config = get_addon_config('signin');
        $signdata = $config['signinscore'];

        list($ranklist, $self_rank, $successions, $is_signin) = Service::getRankInfo();

        $success_day = $successions + 1;
        $score = $signdata['s' . $success_day] ?? $signdata['sn'];

        $msg = $successions ? "你当前已经连续签到 {$successions} 天,明天继续签到可获得 {$score} 积分" : "今天签到可获得 {$score} 积分,请点击签到领取积分";

        $this->success('', [
            'signinscore' => $config['signinscore'], //签到规则
            'isfillup'    => $config['isfillup'], //是否开启补签
            'fillupscore' => $config['fillupscore'], //补签消耗积分
            'fillupdays'  => $config['fillupdays'], //允许补签天数
            'ranklist'    => $ranklist,
            'successions' => $successions,
            'is_signin'   => $is_signin,
            'self_rank'   => $self_rank, //自己的排名
            'score'       => $this->auth->score, //用户积分
            'msg'         => $msg
        ]);
    }

    /**
     * 每月签到情况
     * @return void
     */
    public function monthSign()
    {
        $date = $this->request->param('date', date("Y-m"), "trim");
        $time = strtotime($date);
        $list = \addons\signin\model\Signin::where('user_id', $this->auth->id)
            ->field('id,createtime')
            ->whereTime('createtime', 'between', [date("Y-m-1", $time), date("Y-m-1", strtotime("+1 month", $time))])
            ->select();
        $newData = [];
        foreach ($list as $index => $item) {
            $newData[date('d', $item->createtime)] = date('d', $item->createtime);
        }
        $this->success('', $newData);
    }

    /**
     * 立即签到
     */
    public function dosign()
    {
        $config = get_addon_config('signin');
        $signdata = $config['signinscore'];

        $result = Service::dosign($this->auth->id);
        if (isset($result['errmsg'])) {
            $this->error($result['errmsg']);
        } else {
            $this->success('签到成功!连续签到' . $result['successions'] . '天!获得' . $result['score'] . '积分');
        }
    }

    /**
     * 签到补签
     */
    public function fillup()
    {
        $date = $this->request->param('date');

        $result = Service::fillup($date, $this->auth->id);
        if (isset($result['errmsg'])) {
            $this->error($result['errmsg']);
        } else {
            $this->success('补签成功');
        }
    }

    /**
     * 签到日志
     * @return void
     */
    public function signLog()
    {
        $list = \addons\signin\model\Signin::where('user_id', $this->auth->id)
            ->field('id,successions,type,createtime')->order('createtime desc')->paginate(15);
        foreach ($list as $item) {
            $item->createtime = date('Y-m-d', $item->createtime);
            $item->type = $item->type == 'fillup' ? '补签' : '签到';
        }
        $this->success('', $list);
    }

    /**
     * 排行榜
     */
    public function rank()
    {
        list($ranklist, $self_rank, $successions) = Service::getRankInfo();
        foreach ($ranklist as $item) {
            if (!empty($item['user'])) {
                $item->user->avatar = cdnurl($item->user->avatar, true);
            }
        }
        $this->success("", [
            'ranklist'    => $ranklist,
            'self_rank'   => $self_rank, //自己的排名
            'successions' => $successions //自己的签到
        ]);
    }
}
