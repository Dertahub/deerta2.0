<?php

namespace app\api\controller\deerta;

use app\common\controller\Api;

class Level extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 会员等级
     *
     */
    public function index()
    {
        $userinfo = $this->auth->getUserinfo();

        $level = \app\admin\model\keerta\level\Level::field('id,level_name,level_logo,score,interest_rate,small_rate,sign_reward')
            ->order('id asc')
            ->select();
        foreach ($level as $key => $value) {
            $level[$key]['level_logo'] = $value['level_logo'] ? cdnurl($value['level_logo'], true) : '';
        }

        $this->success('success', [
            'level_list' => $level,
            'user_info' => [
                'level_id' => $userinfo['level_id'],
                'level_name' =>$level[$userinfo['level_id']]['level_name'],
                'level_logo' => $level[$userinfo['level_id']]['level_logo'],
                'score' => $userinfo['score'],
                'avatar' => $userinfo['avatar'],
            ]
        ]);
    }
}