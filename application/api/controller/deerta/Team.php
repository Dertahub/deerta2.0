<?php

namespace app\api\controller\deerta;

use app\common\controller\Api;
use app\common\model\MoneyLog;

class Team extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 团队奖励
     *
     */
    public function index()
    {
        $user = $this->auth->getUser();
        $team = \app\admin\model\keerta\level\Team::where('id', $user['team_id'])
            ->cache(true, 60)
            ->find();
        $data['team_id'] = $user['team_id'];
        $data['level_name'] = $team['level_name'];
        $userId = $user['id'];
        $data['team_people_num'] = \app\common\model\User::where("FIND_IN_SET({$userId}, refer_path)")
            ->where('self_invest_money','>',0)
            ->cache(true, 60)
            ->count();
        $data['direct_people_num'] = \app\common\model\User::where("refer", $userId)
            ->where('self_invest_money','>',0)
            ->cache(true, 60)
            ->count();
        $data['team_invest_money'] = $user['team_invest_money'];

        $team = \app\admin\model\keerta\level\Team::where('id','>', 0)
            ->order('id asc')
            ->field('id,level_name,direct_people,team_people,total_building,reward')
            ->select();
        foreach ($team as &$item){
            $t = MoneyLog::where('user_id', $userId)
                ->where('type', 9)
                ->where('order_sn', 'T'.$item['id'])
                ->cache(true, 3)
                ->find();
            $item['is_reward'] = $t ? 1 : 0;
        }
        $data['team'] = $team;

        $this->success("获取成功", $data);
    }

    /**
     * 领取团队奖励
     *
     */
    public function receive()
    {
        $user = $this->auth->getUser();
        $id = $this->request->param('id', 0, 'int');

        if($user['team_id'] < $id){
            $this->error("当前不可领取该奖励");
        }
        $team = \app\admin\model\keerta\level\Team::where('id', $id)
            ->cache(true, 60)
            ->find();
        $order_sn = 'T'.$id;
        $log = MoneyLog::where('user_id', $user['id'])
            ->where('type', 9)
            ->where('order_sn',$order_sn)
            ->find();
        if ($log) {
            $this->error("已领取");
        }
        MoneyLog::money($user['id'], $team['reward'], 9, 'withdraw_money', $order_sn);

        $this->success("领取成功");
    }

    /**
     * 我的团队
     *
     */
    public function myteam()
    {
        $limit = $this->request->param('limit', 10, 'int');
        $user = $this->auth->getUser();

        $data['self_invest_money'] = $user['self_invest_money'];
        $data['team_invest_money'] = $user['team_invest_money'];
        $team_people_num = \app\common\model\User::where("FIND_IN_SET({$user['id']}, refer_path)")
            ->where('self_invest_money','>',0)
            ->cache(true, 60)
            ->column('id');
        $data['team_people_num'] = count($team_people_num);
        $data['total_recharge'] = MoneyLog::whereIn('user_id', $team_people_num)
            ->where('type', 1)
            ->cache(true, 60)
            ->sum('money');
        $data['total_withdraw'] = abs(MoneyLog::whereIn('user_id', $team_people_num)
            ->where('type', 4)
            ->cache(true, 60)
            ->sum('money'));
        $data['list'] = \app\common\model\User::where("FIND_IN_SET({$user['id']}, refer_path)")
            ->where('self_invest_money','>',0)
            ->order('rgtime desc,id desc')
            ->field('id')
            ->paginate($limit)->each(function (&$item){
                $item['surname'] = \app\admin\model\keerta\Realname::where('user_id', $item['id'])
                    ->order('id desc')
                    ->cache(true, 60)
                    ->value('surname');
                $item['total_recharge'] = MoneyLog::where('user_id', $item['id'])
                    ->where('type', 1)
                    ->cache(true, 60)
                    ->sum('money');
                $item['total_withdraw'] = abs(MoneyLog::where('user_id', $item['id'])
                    ->where('type', 4)
                    ->cache(true, 60)
                    ->sum('money'));
            });

        $this->success("获取成功", $data);
    }


}