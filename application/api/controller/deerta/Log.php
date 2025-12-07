<?php

namespace app\api\controller\deerta;

use app\admin\model\keerta\order\Interest;
use app\common\controller\Api;
use app\common\model\MoneyLog;
use think\Db;

class Log extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
      * 财务明细
      * 1=充值,2=提现，
     *
     */
    public function index()
    {
        $model = new MoneyLog();
        $type = $this->request->param('type', 0, 'int');
        $limit = $this->request->param('limit', 10, 'int');

        $userId = $this->auth->id;
        if ($type == 1){
            // 1=充值
            $model = $model->where('type', 1);
        }elseif ($type == 2){
            // 2=提现
            $model = $model->where('type', 2);
        }elseif ($type == 3){
            // 3=投资送积分
            $model = $model->where('type', 5)
                ->where('symbol', 'score');
        }elseif ($type == 4){
            // 4=转入余额宝
            $model = $model->where('type', 6)
                ->where('money', '<',0);
        }elseif ($type == 5){
            // 5=余额宝转出
            $model = $model->where('type', 6)
                ->where('money', '>',0);
        }elseif ($type == 6){
            // 6=下级建仓返佣金
            $model = $model->where('type', 10);
        }elseif ($type == 7){
            // 7=项目返本金
            $model = $model->where('type', 5)
                ->whereLike('memo', "%本金%");
        }elseif ($type == 8){
            // 8=签到奖励
            $model = $model->where('type', 3);
        }elseif ($type == 9){
            // 9=投资项目
            $model = $model->where('type', 5)
                ->where('money', '<',0);
        }elseif ($type == 10){
            // 10=余额宝返利
            $model = $model->where('type', 7);
        }elseif ($type == 11){
            // 11=投资结束返还分红
            $model = $model->where('type', 5)
                ->whereLike('memo', "%分红%");
        }elseif ($type == 12){
            // 12=货币兑换
            $list = \app\admin\model\keerta\redeem\Redeem::where('user_id', $userId)
                ->order('id desc')
                ->paginate($limit);
        }elseif ($type == 13){
            // 13=充值送红包未知程序
            $model = $model->where('symbol', 'dsorb_money');
        }
        if($type != 12){
            $list = $model->where('user_id', $userId)
                ->field('id,money,memo,createtime,type,symbol,after')
                ->order('id desc')
                ->paginate($limit);
        }


        $user = $this->auth->getUser();
        $data = [
            'money' => $user['money'],
            'usdt' => $user['usdt'],
            'withdraw_money' => $user['withdraw_money'],
            'withdraw_usdt' => $user['withdraw_usdt'],
            'is_bouns1' => Interest::where('user_id', $user['id'])
                ->where('is_bouns', 1)
                ->cache(true, 60)
                ->sum('amount'),
            'is_bouns0' => Interest::where('user_id', $user['id'])
                ->where('is_bouns', 0)
                ->cache(true, 60)
                ->sum('amount'),
        ];
        $this->success("获取成功", [
            'list' => $list,
            'data' => $data,
        ]);
    }

    /**
     * 充值记录
     *
     */
    public function recharge()
    {
        $page = $this->request->get('page', 1,'int');
        $limit = $this->request->get('limit', 10,'int'); // 默认每页20条

        // 2. 构建两个表的查询语句
        $field = 'id,user_id,money,image,status,createtime,reason,memo';
        $sql1 = Db::table('fa_keerta_money')
            ->field($field . ", 'money' as source") // 添加source字段标识来自money表
            ->buildSql();
        $sql2 = Db::table('fa_keerta_usdt')
            ->field($field . ", 'usdt' as source") // 添加source字段标识来自usdt表
            ->buildSql();

        // 3. 使用 UNION ALL 合并查询，并作为子查询
        $unionQuery = Db::table([$sql1 => 'a'])->union($sql2)->buildSql();

        // 4. 对合并后的结果进行最终查询：排序 + 分页
        // 注意：这里是从子查询 'temp' 中再次选择，并排序
        $userId = $this->auth->id;

        $list = Db::table($unionQuery . ' temp')
            ->order('createtime DESC') // 按时间倒序排列，ASC 为正序
            ->where('user_id', $userId)
            ->paginate($limit)->each(function ($item){
                $item['image'] = cdnurl($item['image'], true);
                return $item;
            });
        /*foreach ($list as &$item){
            $item['image'] = cdnurl($item['image'], true);
        }*/



        $this->success("获取成功", [
            'list' => [
                'data' => $list,
            ],
        ]);
    }

    /**
     * 提现记录
     *
     */
    public function withdraw()
    {
        $page = $this->request->get('page', 1,'int');
        $limit = $this->request->get('limit', 10,'int'); // 默认每页20条

        // 2. 构建两个表的查询语句
        $field = 'id,user_id,money,status,createtime,reason,memo';
        $sql1 = Db::table('fa_keerta_withdraw_cny')
            ->field($field . ", 'money' as source") // 添加source字段标识来自money表
            ->buildSql();
        $sql2 = Db::table('fa_keerta_withdraw_usdt')
            ->field($field . ", 'usdt' as source") // 添加source字段标识来自usdt表
            ->buildSql();

        // 3. 使用 UNION ALL 合并查询，并作为子查询
        $unionQuery = Db::table([$sql1 => 'a'])->union($sql2)->buildSql();

        // 4. 对合并后的结果进行最终查询：排序 + 分页
        // 注意：这里是从子查询 'temp' 中再次选择，并排序
        $list = Db::table($unionQuery . ' temp')
            ->where('user_id', $this->auth->id)
            ->order('createtime DESC') // 按时间倒序排列，ASC 为正序
            ->limit($page, $limit)
            ->paginate($limit);


        $this->success("获取成功", [
            'list' => $list,
        ]);
    }

}