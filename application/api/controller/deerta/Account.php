<?php

namespace app\api\controller\deerta;

use app\common\controller\Api;
use app\common\library\Auth;

class Account extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function bankAdd()
    {
        $param = ['bank_card','bank_name','bank_deposit','pay_password'];
        $this->paramValidate($param);

        $params = $this->request->post();
        // 规则验证112
        try {
            $this->validateFailException()->validate($params,'AccountValidate.bankAdd');

        }catch (\Exception $e){
            $this->error($e->getMessage());
        }

        $this->addValidate($params,1);

        $this->success('添加成功');
    }

    public function usdtAdd()
    {
        $param = ['usdt_address','pay_password'];
        $this->paramValidate($param);

        $params = $this->request->post();
        // 规则验证
        try {
            $this->validateFailException()->validate($params,'AccountValidate.usdtAdd');

        }catch (\Exception $e){
            $this->error($e->getMessage());
        }

        $this->addValidate($params, 2);

        $this->success('添加成功');
    }

    /**
     * 添加账户
     */
    public function addValidate($params, $type)
    {
        $user = $this->auth->getUser();
        $pay_password = $this->request->param('pay_password', '', 'trim');
        if ($user->pay_password != (new Auth)->getEncryptPassword($pay_password, $user->salt)) {
            $this->error('支付密码错误！');
        }
        $userId = $this->auth->id;
        $params['user_id'] = $userId;
        $params['type'] = $type;
        unset($params['pay_password']);
        \app\admin\model\keerta\withdraw\Account::create($params);
    }

    /**
     * 账户列表
     */
    public function index()
    {
        $userId = $this->auth->id;

        $list = \app\admin\model\keerta\withdraw\Account::where('user_id', $userId)
            ->order('id desc')
            ->limit(20)
            ->select();

        $this->success('success', [
            'list' => $list
        ]);
    }

    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $userId = $this->auth->id;

        $find = \app\admin\model\keerta\withdraw\Account::where('id', $id)
            ->where('user_id', $userId)
            ->find();
        if (!$find) {
            $this->error('数据不存在');
        }
        $find->delete();
        $this->success('删除成功');
    }
}