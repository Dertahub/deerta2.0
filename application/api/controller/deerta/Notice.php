<?php

namespace app\api\controller\deerta;

use app\common\controller\Api;
use think\Config;

class Notice extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

        $this->model = new \app\admin\model\keerta\Notice();
    }

    /**
     * 首页弹出
     *
     */
    public function pop()
    {
        $pop = $this->model->where('switch', 1)
            ->where('home_pop', 1)
            ->whereTime('publishtime', '<=', time())
            ->order('weigh desc,id desc')
            ->field('id,title,image,content')
            ->find();
        if (!$pop){
            $this->success('暂无数据',null);
        }
        $pop['image'] = $pop['image'] ? cdnurl($pop['image'], true) : '';

        $this->success('获取成功', $pop);
    }
    /**
     * @return void
     * 获取公告列表
     */
    public function list()
    {
        $cate_id = $this->request->get('cate_id', 0, 'int');
        if (!$cate_id){
            $this->error('请选择分类');
        }
        $limit = $this->request->get('limit', 10, 'int');
        $is_recommend = $this->request->get('is_recommend', 0, 'int');
        if ($is_recommend){
            $this->model->where('home', 1);
        }
        $list = $this->model->where('switch', 1)
            ->where('cate_id', $cate_id)
            ->whereTime('publishtime', '<=', time())
            ->field('id,title,image')
            ->order('weigh desc,id desc')
            ->paginate($limit)->each(function (&$item){
                $item['image'] = $item['image'] ? cdnurl($item['image'], true) : '';
            });

        $this->success('获取成功', $list);
    }

    /**
     * @return void
     * 获取公告详情
     */
    public function detail()
    {
        $id = $this->request->param('id',  '', 'int');
        if (!$id) {
            $this->error('参数错误');
        }
        $detail = $this->model->where('id', $id)
            ->where('switch', 1)
            ->whereTime('publishtime', '<=', time())
            ->cache(true, 60)
            ->field('id,title,content,publishtime')
            ->find();
        if (!$detail) {
            $this->error('资讯不存在');
        }
        $detail['content'] = $detail['content'] ? replace_content_file_url($detail['content']) : '';

        $this->success('获取成功', $detail);
    }

    /**
     * 公司资质
     *
     */
    public function company()
    {
        $detail = $this->model->where('title', "公司资质")
            ->where('switch', 1)
            ->order('weigh desc,id desc')
            ->cache(true, 60)
            ->field('id,title,content,publishtime')
            ->find();
        if (!$detail) {
            $this->error('内容不存在');
        }

        $this->success('获取成功', $detail);
    }

}