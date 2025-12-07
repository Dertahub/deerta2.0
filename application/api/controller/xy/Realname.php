<?php

namespace app\api\controller\xy;

use app\admin\model\keerta\Realnamesetting;
use app\common\controller\Api;
use app\common\service\IdcardService;
use Exception;
use think\exception\ValidateException;

class Realname extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 实名配置‘
     *
     */
    public function config()
    {
        $config = Realnamesetting::where('id', 1)
            ->cache(true, 60)
            ->field('red')
            ->find();
        $this->success('success', $config);
    }

    /**
     * 实名认证提交
     *
     */
    public function submit()
    {
        $params = $this->request->post();
        $userId = $this->auth->id;
        lock('realName' . $userId);

        $param = ['surname','idcard','idcard_image','idcard_image2','signature'];
        $this->paramValidate($param);

        // 规则验证
        try {
            $this->validateFailException()->validate($this->params,'RealnameValidate.submit');

            IdcardService::validateIdCard($params['idcard']);
        }catch (Exception|ValidateException $e){
            $this->error($e->getMessage());
        }
        $idcard = \app\admin\model\keerta\Realname::where('user_id', $userId)
            ->where('status',0)
            ->find();
        if ($idcard){
            $this->error('正在审核中，请耐心等待审核');
        }
        $idcard = \app\admin\model\keerta\Realname::where('idcard', $params['idcard'])
            ->whereIn('status',[0,1])
            ->find();
        if ($idcard){
            $this->error('该身份证已被使用');
        }


        $params['user_id'] = $userId;

        \app\admin\model\keerta\Realname::create($params);

        $user = \app\common\model\User::where('id', $userId)->find();
        $user->realname_status = 1;
        $user->save();

        $this->success('提交成功，请耐心等待审核');
    }

    /**
     * 我的实名信息
     *
     */
    public function my()
    {
        $userId = $this->auth->id;
        $idcard = \app\admin\model\keerta\Realname::where('user_id', $userId)
            ->order('id desc')
            ->find();
        if(!$idcard){
            $this->error('未提交实名信息');
        }
        $idcard['idcard_image'] = $idcard['idcard_image'] ? $this->request->domain() . $idcard['idcard_image'] : '';
        $idcard['idcard_image2'] = $idcard['idcard_image2'] ? $this->request->domain() . $idcard['idcard_image2'] : '';
        $idcard['signature'] = $idcard['signature'] ? $this->request->domain() . $idcard['signature'] : '';

        $this->success('success', $idcard);
    }
}