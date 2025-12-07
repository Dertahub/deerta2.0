<?php

namespace app\api\controller\deerta;

use app\admin\model\keerta\Goodsrules;
use app\common\controller\Api;

class Goods extends Api
{
    protected $noNeedLogin = ['index'];
    protected $noNeedRight = ['*'];

    /**
     * 产品列表
     *
     * @return void
     */
    public function index()
    {
        $cate_id = $this->request->get('cate_id', 0, 'int');
        $is_hot = $this->request->get('is_hot', 0, 'int');
        $limit = $this->request->get('limit', 10, 'int');

        $model = new \app\admin\model\keerta\goods\Goods();
        if($cate_id){
            $model->where('cate_id', $cate_id);
        }
        if($is_hot){
            $model->where('is_hot', 1);
        }

        $list = $model->where('switch', 1)
            ->field('id,goods_name,total_amount,surplus_amount,interest_days,limit_num,day_profit_rate,start_cycle,start_amount,bouns_rate,red_envelope_amount,virtual_sales_amount')
            ->order('weigh desc, id desc')
            ->cache(true, 6)
            ->paginate($limit)->each(function(&$item){
                $total_amount = $item['total_amount'];
                if($item['total_amount'] > 10000){
                    $item['total_amount'] = round($item['total_amount'] / 10000, 2) . '万';
                }
                $sales = bcsub($total_amount, $item['surplus_amount'], 2);
                $item['sales_progress'] = round(round($sales / $total_amount, 2) * 100, 2);
/*                if($item['virtual_sales_amount'] > 0){
                    $sales_progress = round($item['virtual_sales_amount'] / $total_amount, 2) * 100;
                    if($sales_progress > $item['sales_progress']){
                        $item['sales_progress'] = $sales_progress;
                    }
                }*/
                $item['sales_progress'] = min($item['sales_progress'], 100);
            });

        $this->success('请求成功', ['list' => $list]);
    }

    /**
     * 产品详情
     *
     */
    public function detail()
    {
        $id = $this->request->get('id', 0, 'int');
        $model = new \app\admin\model\keerta\goods\Goods();
        $detail = $model->where('id', $id)->where('switch', 1)->find();
        if(!$detail){
            $this->error('产品不存在');
        }
        $detail['kpis_image'] = $detail['kpis_image'] ? cdnurl($detail['kpis_image'], true) : '';
        $detail['guarantee_company_image'] = $detail['guarantee_company_image'] ? cdnurl($detail['guarantee_company_image'], true) : '';
        $total_amount = $detail['total_amount'];
        if($total_amount > 100){
            $detail['total_amount'] = round($detail['total_amount'] / 10000, 2) . '万';
        }
        $surplus_amount = $detail['surplus_amount'];
        if($surplus_amount > 100){
            $detail['surplus_amount'] = round($detail['surplus_amount'] / 10000, 2) . '万';
        }

        $sales = bcsub($total_amount, $surplus_amount, 2);
        $detail['sales_progress'] = round(round($sales / $total_amount, 2) * 100, 2);
        /*if($detail['virtual_sales_amount'] > 0){
            $sales_progress = round($detail['virtual_sales_amount'] / $total_amount, 2) * 100;
            if($sales_progress > $detail['sales_progress']){
                $detail['sales_progress'] = $sales_progress;
            }
        }*/
        $detail['sales_progress'] = min($detail['sales_progress'], 100);

        $detail['cate_name'] = \app\admin\model\keerta\Goodscate::where('id', $detail['cate_id'])->value('name');
        $detail['type_name'] = \app\admin\model\keerta\Goodstype::where('id', $detail['type_id'])->value('name');
        $images= $detail['images'] ? explode(',', $detail['images']) : [];
        if ($images){
            foreach ($images as &$image){
                $image = cdnurl($image, true);
            }
        }
        $detail['images'] = $images;
        $user = $this->auth->getUser();
        $detail['profit'] = $this->profit($user['level_id'], $detail);
        $this->success('请求成功', $detail);
    }

    /**
     * 收益计算
     *
     */
    public function profit($level_id,$goods)
    {
        $level = \app\admin\model\keerta\Level::where('id', $level_id)
            ->cache(true, 60)
            ->find();
        if(!$level){
            $this->error('等级不存在！');
        }
        // 每天利息金额
        $days_interest_amount = $goods['start_amount'] * ($goods['day_profit_rate']/100 + $level['interest_rate']/100);
        // 每次利息金额
        $interest_aomunt = $days_interest_amount * $goods['interest_days'];
        // 总收益=投资金额*（平均日益+会员等级加息）*招募周期
        $interest_total_amount = round($days_interest_amount * $goods['start_cycle'], 2);

        // 分红金额的计算
        $bouns_amount = round($interest_total_amount * $goods['bouns_rate']/100, 2);
        return [
            'amount' => $goods['start_amount'],
            'day_profit_rate' => $goods['day_profit_rate'],
            'interest_rate' => $level['interest_rate'],
            'start_cycle' => $goods['start_cycle'],
            'interest_total_amount' => $interest_total_amount,
            'bouns_rate' => $goods['bouns_rate'],
            'red_envelope_amount' => $goods['red_envelope_amount'],
            'bouns_amount' => $bouns_amount,
            'total_profit' => round($interest_total_amount + $bouns_amount + $goods['red_envelope_amount'], 2),
        ];
    }

    /**
     * 产品规则
     *
     */
    public function rules()
    {
        $list = Goodsrules::order('id asc')
            ->field('id,title,content')
            ->select();
        $this->success('请求成功', ['list' => $list]);
    }

}