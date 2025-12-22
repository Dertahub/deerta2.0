<?php

namespace app\admin\command;

use app\admin\model\keerta\level\Log;
use app\admin\model\keerta\level\Team;
use app\common\model\User;
use getID3;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class Test extends Command
{

    protected function configure()
    {
        $this->setName('test')
            ->setDescription('test');
    }

    /**
     * @param Input $input
     */
    protected function execute(Input $input, Output $output)
    {
        $user = User::where('id',445601)->find();
        $this->teamLevelUpdate($user);
        exit();
    }
    public function teamLevelUpdate($team_user)
    {
        $team_id = 0;
        // 团队等级的判断
        if($team_user['team_id'] < 8){
            // 直推人数
            $teamUid = $team_user['id'];
            $count = \app\common\model\User::where('refer', $teamUid)
                ->where('self_invest_money','>',0)
                ->count();
            $team_count = \app\common\model\User::where("FIND_IN_SET({$teamUid}, refer_path)")
                ->where('self_invest_money','>',0)
                ->count();
            $team_user = \app\common\model\User::where("FIND_IN_SET({$teamUid}, refer_path)")
                ->where('self_invest_money','>',0)
                ->column('id');

            var_dump($count, $team_count,$team_user);exit();

            /*if($count > 0 && $team_count > 0){
                $team = Team::where('direct_people','<', $count)
                    ->where('team_people', '<', $team_count)
                    ->where('total_building','<',$team_user->team_invest_money)
                    ->order('id desc')
                    ->find();
                if($team['id'] > $team_user['team_id']){

                    Log::create([
                        'user_id' => $teamUid,
                        'level_id' => $team['id'],
                        'memo' => '团队等级提升为'.$team['id'],
                        'type'=>2,
                    ]);
                    $team_id = $team['id'];
                }
            }*/
        }
        return $team_id;
    }

}