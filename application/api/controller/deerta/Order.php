<?php

namespace app\api\controller\deerta;

use app\admin\model\keerta\level\Log;
use app\admin\model\keerta\level\Team;
use app\admin\model\keerta\order\Interest;
use app\common\controller\Api;
use app\common\library\Auth;
use app\common\model\MoneyLog;
use Exception;
use think\Db;

class Order extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 下单
     *
     */
    public function pay()
    {
        $begin_time = time();
        $param = ['goods_id','amount','pay_password','handwritten_signature'];
        $this->paramValidate($param);

        $params = $this->request->post();
        // 规则验证
        try {
            $this->validateFailException()->validate($params,'OrderValidate.pay');
        }catch (Exception $e){
            $this->error($e->getMessage());
        }
        $goods_id = $params['goods_id'];
        $amount = $params['amount'];
        lock('order_pay_'.$goods_id,'产品锁定中,请稍后再试...', 6);

        $goods = \app\admin\model\keerta\goods\Goods::where('id', $goods_id)
            ->where('switch', 1)
            ->find();
        if(!$goods){
            $this->error('产品不存在！');
        }
        $user = $this->auth->getUser();

        try {
            $this->orderValidate($user, $goods);
        }catch (Exception $e){
            unlock('order_pay_'.$goods_id);
            $this->error($e->getMessage());
        }

        $level = \app\admin\model\keerta\Level::where('id', $user['level_id'])->find();
        if(!$level){
            unlock('order_pay_'.$goods_id);
            $this->error('等级不存在！');
        }
        // 每天利息金额
        $days_interest_amount = $amount * ($goods['day_profit_rate']/100 + $level['interest_rate']/100);
        // 每次利息金额
        $interest_aomunt = $days_interest_amount * $goods['interest_days'];
        // 总收益=投资金额*（平均日益+会员等级加息）*招募周期
        $interest_total_amount = $days_interest_amount * $goods['start_cycle'];
        // 分红金额的计算
        $bouns_amount = $interest_total_amount * $goods['bouns_rate']/100;

        $order_sn = 'T' . date('mdHi') . chr(rand(65, 90)) . rand(1, 9) . chr(rand(65, 90)) . rand(100, 999);
        // 订单数据
        $orderArr = [
            'goods_id' => $goods_id,
            'user_id' => $user['id'],
            'amount' => $amount,
            'order_sn' => $order_sn,
            'goods_name' => $goods['goods_name'],
            'bouns_rate' => $goods['bouns_rate'], // 分红比例
            'bouns_amount' => $bouns_amount, // 分红金额
            'red_envelope_amount' => $goods['red_envelope_amount'], // 红包金额
            'day_profit_rate' => $goods['day_profit_rate'], // 日利率
            'start_cycle' => $goods['start_cycle'], // 起购周期
            'interest_rate' => $level['interest_rate'], // 加息比例
            'interest_days' => $goods['interest_days'], // 每几日利息产生利息
            'interest_num' => $goods['interest_num'], // 利息次数
            'interest_aomunt' => $interest_aomunt,// 每次利息金额
            'interest_total_amount' => $interest_total_amount, // 总利息
            'score' => $amount, // 积分
            'handwritten_signature' => $params['handwritten_signature'],
            'createtime' => time(),
        ];

        Db::startTrans();
        try {
            // 创建订单
            \app\admin\model\keerta\order\Order::create($orderArr);
            // 扣除用户余额
            MoneyLog::money($user['id'], -$amount, 5, 'money', $order_sn);
            // 添加积分
            MoneyLog::money($user['id'], $amount, 5, 'score', $order_sn);
            // 赠送红包
            MoneyLog::money($user['id'], $goods['red_envelope_amount'], 5, 'withdraw_money', $order_sn);

            // 扣减产品可投资金额
            $goods->surplus_amount -= $amount;
            $goods->save();

            // 记录所有的利息、分红、本金、红包记录
            // 创建时间
            $createtime = time();
            // 红包
            $interest_log[] = [
                'order_sn' => $order_sn,
                'user_id' => $user['id'],
                'amount' => $goods['red_envelope_amount'],
                'interest_num' => 1,
                'type' => 2,
                'bouns_date'=>date('Y-m-d'),
                'is_bouns' => 1,
                'createtime' => $createtime,
                'updatetime' => $createtime,
                'bounstime' => $createtime
            ];
            // 利息
            $start_time = strtotime('+1 day');
            $bouns_start_time = $createtime;
            $bouns_date = date('Y-m-d', $start_time);
            for ($i = 1; $i <= $goods['interest_num']; $i++) {
                $bouns_date = date('Y-m-d', $start_time+$i*$goods['interest_days']*86400);
                $bounstime = $bouns_start_time+$i*$goods['interest_days']*86400;
                $interest_log[] = [
                    'order_sn' => $order_sn,
                    'user_id' => $user['id'],
                    'amount' => $interest_aomunt,
                    'interest_num' => $i,
                    'type' => 1,
                    'bouns_date'=>$bouns_date,
                    'is_bouns' => 0,
                    'createtime' => $createtime,
                    'updatetime' => $createtime,
                    'bounstime' => $bounstime
                ];
            }

            // 分红
            $interest_log[] = [
                'order_sn' => $order_sn,
                'user_id' => $user['id'],
                'amount' => $bouns_amount,
                'interest_num' => 1,
                'type' => 3,
                'bouns_date'=>$bouns_date,
                'is_bouns' => 0,
                'createtime' => $createtime,
                'updatetime' => $createtime,
                'bounstime' => $bounstime
            ];
            // 本金
            $interest_log[] = [
                'order_sn' => $order_sn,
                'user_id' => $user['id'],
                'amount' => $amount,
                'interest_num' => 1,
                'type' => 4,
                'bouns_date'=>$bouns_date,
                'is_bouns' => 0,
                'createtime' => $createtime,
                'updatetime' => $createtime,
                'bounstime' => $bounstime
            ];
            $model = new Interest();
            $model->saveAll($interest_log);

            // 生成合同
            $this->sign($orderArr, $bounstime, $level);

            // 增加个人的累计建仓金额
            $user->self_invest_money += $amount;
            $user->team_invest_money += $amount; // 团队累计建仓金额含个人
            $userTeamId = $this->teamLevelUpdate($user);
            if($userTeamId > 0){
                $user->team_id = $userTeamId;
            }
            $user->save();

            // 添加团队业绩
            $team = explode(',', $user['refer_path']);
            foreach ($team as $v) {
                $team_user = \app\common\model\User::where('id', $v)->find();
                if ($team_user) {
                    $team_user->team_invest_money += $amount;
                    $teamId = $this->teamLevelUpdate($team_user);
                    if($teamId > 0){
                        $team_user->team_id = $teamId;
                    }
                    $team_user->save();
                }
            }

            // 给上级进行返佣
            $this->teamBouns($user['refer'], $orderArr);

            $end_time = time();
            if ($end_time - $begin_time > 10){
                throw new Exception('订单创建超时！');
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            unlock('order_pay_'.$goods_id);
            $this->error($e->getMessage());
        }

        unlock('order_pay_'.$goods_id);
        $this->success('购买成功！');
    }

    /**
     * 团队返佣
     *
     */
    public function teamBouns($refer_id, $order, $level = 1)
    {
        $amount = $order['amount'];
        $memo = [
            1=>'一代团队返佣',
            2=>'二代团队返佣',
            3=>'三代团队返佣'
        ];
        $rate = [
            1=>'one_rate',
            2=>'two_rate',
            3=>'three_rate'
        ];
        $refer = \app\common\model\User::where('id', $refer_id)->find();
        if ($refer) {
            $team = Team::getTeam($refer['team_id']);
            $rate = $team[$rate[$level]];
            $refer_amount = $amount * $rate / 100;
            MoneyLog::money($refer['id'], $refer_amount, 10, 'withdraw_money', $order['order_sn'], $team['level_name'] . $memo[$level]);
            $level++;
            if($level <= 3){
                $this->teamBouns($refer['refer'], $order, $level);
            }
        }
    }
    /**
     * 合同签订
     *
     */
    public function sign($order, $bounstime, $level = 0)
    {
        $treaty_info = \app\admin\model\keerta\treaty\Treaty::where('id',1)
            ->find();
        if (!$treaty_info) {
            throw new Exception("该协议不存在");
        }

        $realname = \app\admin\model\keerta\Realname::where('user_id', $order['user_id'])->find();
        if (!$realname){
            throw new Exception("用户信息不存在");
        }

        $signature = [
            'surname' => $realname['surname'], //姓名
            'order_sn' => $order['order_sn'], //合同编号
            'goods_name' => $order['goods_name'], //产品名称
            'idcard' => $realname['idcard'],//身份证
            'amount' => $order['amount'],//投资本金金额
            'start_cycle' => $order['start_cycle'],//收益周期
            'interest_days' => $order['interest_days'],//结算周期
            'profit_rate' => $order['day_profit_rate'],//收益率
            'interest_rate' =>$level['level_name'] . '加息' . $order['interest_rate'],//加息率
            'total_interest_rate' => round($order['interest_rate'] + $order['day_profit_rate'], 2),//收益率
            'end_date' => date('Y年m月d日', $bounstime),//到期日
            'interest_total_amount' => round($order['amount'] + $order['red_envelope_amount'] + $order['bouns_amount'] + $order['interest_total_amount'], 2),//应收本息
            'date' => date('Y年m月d日'),//日期
        ];

        foreach ($signature as $key => $value){
            $treaty_info["content"] = str_replace("【".$key."】", $value,$treaty_info["content"]);
        }

        $dataArr = [
            'user_id' => $order['user_id'],
            'order_sn' => $order['order_sn'],
            'content' => $treaty_info['content'],
            'official_seal_image' => $treaty_info['official_seal_image'],
            'official_seal_image2' => $treaty_info['official_seal_image2'],
        ];
        \app\admin\model\keerta\treaty\Sign::create($dataArr);
    }
    /**
     * 团队等级的判断级更新
     *
     */
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
            }
        }
        return $team_id;
    }
    /**
     * 订单验证
     *
     * @throws Exception
     */
    public function orderValidate($user, $goods)
    {
        if($user['realname_status'] != 2){
            throw new Exception('请先完成实名认证！');
        }
        $realname = \app\admin\model\keerta\Realname::where('user_id', $user['id'])->find();
        if(!$realname){
            throw new Exception('请先完成实名认证！');
        }
        if($realname['status'] != 1){
            throw new Exception('请先完成实名认证！');
        }
        $pay_password = $this->request->param('pay_password', '', 'trim');
        if ($user->pay_password != (new Auth)->getEncryptPassword($pay_password, $user->salt)) {
            throw new Exception('支付密码错误！');
        }

        $amount = $this->request->param('amount', 0, 'float');
        if($user['money'] < $amount){
            throw new Exception('余额不足！');
        }

        if($goods['surplus_amount'] <= 0){
            throw new Exception('产品已售完！');
        }
        if($amount > $goods['surplus_amount']){
            throw new Exception('产品可售余额不足！');
        }
        // 投资金额判断
        if($amount < $goods['start_amount']){
            throw new Exception('投资金额不能低于'.$goods['start_amount'].'！');
        }
        if($amount > $goods['single_amount']){
            throw new Exception('单次投资金额不能高于'.$goods['single_amount'].'！');
        }
        // 购买次数
        $buy_num = \app\admin\model\keerta\order\Order::where('goods_id', $goods['id'])
            ->where('user_id', $user['id'])
            ->count();
        if($goods['limit_num'] > 0 && $buy_num >= $goods['limit_num']){
            throw new Exception('该产品购买次数已达上限！');
        }
        // 等级判断
        $level_ids = explode(',', $goods['level_ids']);
        if(!in_array($user['level_id'], $level_ids)){
            throw new Exception('VIP'.$level_ids[0].'可以购买，您没有购买该产品的权限');
        }

    }
    /**
     * 订单列表
     *
     */
    public function list()
    {
        $userId = $this->auth->id;
        $limit = $this->request->param('limit', 10, 'intval');

        $list = \app\admin\model\keerta\order\Order::where('user_id', $userId)
            ->order('id desc')
            ->field('id,order_sn,goods_name,amount,start_cycle,day_profit_rate,interest_total_amount,bouns_amount,red_envelope_amount')
            ->paginate($limit)->each(function (&$item){
                $item['total_profit'] = round($item['interest_total_amount'] + $item['bouns_amount'] + $item['red_envelope_amount'], 2);
            });

        $received_bx = Interest::where('user_id', $userId)
            ->where('is_bouns', 1)
            ->cache(true, 60)
            ->sum('amount');

        $uncollected_bx = Interest::where('user_id', $userId)
            ->where('is_bouns', 0)
            ->cache(true, 60)
            ->sum('amount');

        $this->success('success', [
            'received_bx' => $received_bx,
            'uncollected_bx' => $uncollected_bx,
            'list' => $list,
        ]);
    }

    /**
     * 返利明细
     *
     */
    public function rebate()
    {
        $limit = $this->request->param('limit', 10, 'intval');
        $id = $this->request->param('id', 0, 'int');
        if (!$id){
            $this->error('参数错误！');
        }
        $order = \app\admin\model\keerta\order\Order::where('id', $id)->find();
        if (!$order){
            $this->error('订单不存在！');
        }

        $received_bx = Interest::where('order_sn', $order['order_sn'])
            ->where('is_bouns', 1)
            ->cache(true, 60)
            ->sum('amount');

        $uncollected_bx = Interest::where('order_sn', $order['order_sn'])
            ->where('is_bouns', 0)
            ->cache(true, 60)
            ->sum('amount');

        $log = Interest::where('order_sn', $order['order_sn'])
            ->order('id desc')
            ->field('id,order_sn,type,bouns_date,amount,createtime,is_bouns,bounstime')
            ->paginate($limit)->each(function (&$item) use($order){
                if ($item['type'] == 1){
                    // 利息
                    $item['day_profit_rate'] = bcadd($order['day_profit_rate'], $order['interest_rate'], 2);
                }elseif ($item['type'] == 2){
                    // 红包
                    $item['day_profit_rate'] = bcmul(bcdiv($item['amount'], $order['amount'], 2), 100, 2);
                }elseif ($item['type'] == 3){
                    // 分红
                    $item['day_profit_rate'] = $order['bouns_rate'];
                }elseif($item['type'] == 4){
                    // 本金
                    $item['day_profit_rate'] = 100;
                }else{
                    $item['day_profit_rate'] = 0;
                }

            });

        $this->success('success', [
            'received_bx' => $received_bx,
            'uncollected_bx' => $uncollected_bx,
            'log' => $log,
        ]);
    }


}