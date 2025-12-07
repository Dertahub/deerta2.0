<?php

namespace app\api\controller\xy;

use app\common\controller\Api;

class Version extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $downloadurl = \app\common\model\Version::where('id',1)
            ->field('downloadurl,downloadurl_ios,newversion as version')
            ->cache(true, 60)
            ->find();

        $this->success('success',['version'=>$downloadurl]);
    }

}