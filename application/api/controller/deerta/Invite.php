<?php

namespace app\api\controller\deerta;

use app\admin\model\keerta\level\Team;
use app\common\controller\Api;
use app\common\library\Auth;
use app\common\model\Version;

class Invite extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $user = $this->auth->getUser();

        if($user['realname_status'] != 2){
            $this->error('请先完成实名认证');
        }
        if($user['self_invest_money'] == 0){
            $this->error('暂无邀请权限');
        }
        if(empty($user['invite_code'])){
            $auth = new Auth();
            $invite_code = $auth->generateUniqueInviteCode();
            $user->invite_code = $invite_code;
            $user->save();
        }else{
            $invite_code = $user['invite_code'];
        }

        $downloadurl = Version::where('id',1)
            ->field('downloadurl, image,downloadurl_ios,image_ios,newversion as version')
//            ->cache(true, 60)
            ->find();
        $downloadurl['image'] = cdnurl($downloadurl['image'], true);
        $downloadurl['image_ios'] = cdnurl($downloadurl['image_ios'], true);
        $teamLevel = Team::order('id asc')->select();
        $this->success('success',[
            'invite_code' => $invite_code,
            'downloadurl' => $downloadurl,
            'team_level' => $teamLevel
        ]);


    }
}