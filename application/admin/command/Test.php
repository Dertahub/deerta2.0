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
        $users = User::where('team_invest_money','>',200000)->select();
        foreach ($users as $user){
            $team_id = $this->teamLevelUpdate($user);
            if($team_id > $user['team_id']){
                $user->team_id = $team_id;
                $user->save();
                echo $user['id'].'团队等级更新成功'.$team_id ."\n";
            }
        }

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

            if($count > 0 && $team_count > 0){
                $team = Team::where('direct_people','<=', $count)
                    ->where('team_people', '<=', $team_count)
                    ->where('total_building','<=',$team_user->team_invest_money)
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
            }
        }
        return $team_id;
    }

}