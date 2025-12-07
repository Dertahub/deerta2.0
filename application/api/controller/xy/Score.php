<?php

namespace app\api\controller\xy;

use app\common\controller\Api;
use app\common\model\MoneyLog;
use think\Db;

class Score extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 分类列表
     *
     */
    public function category()
    {
        $list = \app\admin\model\keerta\score\Category::where('switch',1)
            ->order('weigh desc,id desc')
            ->field('id,name')
            ->select();

        $this->success('成功',['list'=>$list]);
    }

    /**
     * 商品列表
     *
     */
    public function goods()
    {
        $limit = $this->request->param('limit',10,'int');
        $category_id = $this->request->param('category_id',0,'int');

        $model = new \app\admin\model\keerta\score\Goods();
        if($category_id > 0){
            $model = $model->where('category_id',$category_id);
        }
        $list = $model->where('switch',1)
            ->order('weigh desc,id desc')
            ->field('id,goods_name,image,score,stock')
            ->paginate($limit)->each(function(&$item){
                $item['image'] = cdnurl($item['image'], true);
            });

        $this->success('成功',['list'=>$list]);
    }

    /**
     * 详情
     *
     */
    public function detail()
    {
        $id = $this->request->param('id',0,'int');
        if (!$id){
            $this->error('请选择商品');
        }
        $goods = \app\admin\model\keerta\score\Goods::where('id',$id)
            ->where('switch',1)
            ->field('id,goods_name,image,images,score,stock,content')
            ->find();

        if (!$goods){
            $this->error('商品不存在');
        }
        $goods['image'] = cdnurl($goods['image'], true);
        $goods['images'] = array_map(function($item){
            return cdnurl($item, true);
        },explode(',',$goods['images']));

        $this->success('成功',$goods);
    }

    /**
     * 兑换
     *
     */
    public function exchange()
    {
        $begin = time();
        $param = ['id','receiver_name','receiver_address','mobile','num'];
        $this->paramValidate($param);

        $id = $this->request->param('id',0,'int');
        if (!$id){
            $this->error('请选择商品');
        }
        lock('score_exchange_'.$id, '请勿频繁操作', 60);
        $receiver_name = $this->request->param('receiver_name','','trim');
        $receiver_address = $this->request->param('receiver_address','','trim');
        $mobile = $this->request->param('mobile','','trim');
        if (!$receiver_name || !$receiver_address || !$mobile){
            $this->error('请填写收货信息');
        }
        $num = $this->request->param('num',1,'int');
        if ($num <= 0){
            $this->error('请填写商品数量');
        }

        $userId = $this->auth->id;
        Db::startTrans();
        try{
            $goods = \app\admin\model\keerta\score\Goods::where('id',$id)
                ->where('switch',1)
                ->find();

            if (!$goods){
                throw new \Exception('商品不存在');
            }
            if ($goods['stock'] <= 0){
                throw new \Exception('商品已售完');
            }
            if ($goods['stock'] < $num){
                throw new \Exception('商品库存不足');
            }
            $user = \app\common\model\User::where('id',$userId)->find();
            if ($user['score'] < $goods['score'] * $num){
                throw new \Exception('积分不足');
            }
            $order_sn = 'S' . date('mdHi') . chr(rand(65, 90)) . rand(1, 9) . chr(rand(65, 90)) . rand(100, 999);
            MoneyLog::money($userId, -$goods['score'] * $num,8,'score', $order_sn);

            $goods->stock -= 1;
            if ($goods->stock <= 0){
                throw new \Exception('商品库存不足！');
            }
            $goods->sales += 1;
            $goods->save();

            \app\admin\model\keerta\score\Order::create([
                'user_id' => $userId,
                'goods_id' => $id,
                'goods_name' => $goods['goods_name'],
                'order_sn' => $order_sn,
                'order_status' => 1,
                'score' => $goods['score'] * $num,
                'num' => $num,
                'mobile' => $mobile,
                'receiver_name' => $receiver_name,
                'receiver_address' => $receiver_address,
            ]);

            if (time() - $begin > 5){
                throw new \Exception('请求超时');
            }

            Db::commit();
        }catch(\Exception $e){
            Db::rollback();
            unlock('score_exchange_'.$id);
            $this->error($e->getMessage());
        }

        $this->success('兑换成功');
    }

    /**
     * 兑换记录
     *
     */
    public function log()
    {
        $userId = $this->auth->id;
        $limit = $this->request->param('limit',10,'int');

        $list = \app\admin\model\keerta\score\Order::where('user_id',$userId)
            ->order('id desc')
            ->field('id,goods_name,order_sn,order_status,score,num,mobile,receiver_name,receiver_address,createtime,memo')
            ->paginate($limit);

        $this->success('成功',['list'=>$list]);
    }

    /**
     * 确认收货
     *
     */
    public function confirm()
    {
        $id = $this->request->param('id',0,'int');
        if (!$id){
            $this->error('请选择订单');
        }
        $order = \app\admin\model\keerta\score\Order::where('id',$id)
            ->where('user_id',$this->auth->id)
            ->find();
        if (!$order){
            $this->error('订单不存在');
        }
        if ($order['order_status'] != 2){
            $this->error('订单状态错误');
        }
        \app\admin\model\keerta\score\Order::where(['id'=>$id])->update([
            'order_status' => 3,
            'confirmtime' => time(),
        ]);
        $this->success('确认收货成功');
    }
}