<?php

namespace addons\treaty\controller;

use app\admin\model\treaty\Treaty as TreatyModel;
use app\admin\model\treaty\Category;
use fast\Random;
use think\addons\Controller;
use think\Cache;
use think\Validate;

class Index extends Controller
{
    protected $layout = 'treaty';

    protected $config = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        if ($this->request->isPost()) {

            $category_id = $this->request->post('category_id');
            $images = $this->request->post('images/a');


            $token = $this->request->post('__token__');

            //判断协议是否存在
            $treaty_find = Category::find($category_id);
            if (!$treaty_find) {
                $this->error("协议不存在");
            }
            $rule = [
                'category_id' => 'require',
                '__token__' => 'require|token',
            ];

            $msg = [
                "category_id.require" => "协议错误"
            ];
            $data = [
                'category_id' => $category_id,
                'images' => $images,
                '__token__'   => $token,
            ];



            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            //判断签名是否都提交了
            $signature = [];
            if($treaty_find['signature']){
                $signature = json_decode($treaty_find['signature'], true);
            }
            //如果登录了记录下会员id
            if($this->auth->isLogin()){
                $data["user_id"] = $this->auth->id;
            }
            //检测登入并生成缓存名称
            if($treaty_find["check_login"]){
                if(!$this->auth->isLogin()){
                    $this->error("请登入后提交", "/index/user/login");
                }
                $checkSign = TreatyModel::where("user_id", $this->auth->id)->where("category_id",$data["category_id"])->find();
                if($checkSign && !$treaty_find['check_repeat']){
                    $this->error( "请勿重复提交", null, ['token' => $this->request->token()]);
                }
            }else{
                /*$cacheName = "treaty_interval_ip_" . request()->ip();
                if(Cache::has($cacheName) && !$treaty_find['check_repeat']){
                    $this->error( "请勿重复提交", null, ['token' => $this->request->token()]);
                }*/
            }


            foreach ($signature as $signatureKey => $signatureValue){
                $is_sign = false;
                if($data["images"]){
                    foreach ($data["images"] as $imagesKey => $imagesValue){
                        if($imagesValue["name"] == $signatureKey){
                            $is_sign = true;
                            break;
                        }
                    }
                }
                if(!$is_sign){
                    $this->error('请处理未签字的部分', null, ['token' => $this->request->token()]);
                }
            }
            //将images处理成json
            if($data["images"]){
                $data["images"] = json_encode($data["images"]);
            }else{
                $data['images'] = "";
            }
            //生成唯一标志
            $code = Random::uuid();
            $data['code'] = $code;
            $res = TreatyModel::create($data, true);
            if ($res) {
                switch ($treaty_find["jump_type"]){
                    case 1:
                        $jump_url = addon_url('treaty/index/index',["treaty_id"=>$category_id]);
                        break;
                    case 2:
                        $jump_url = $treaty_find["jump_url"]?$treaty_find["jump_url"]:addon_url('treaty/index/index',["treaty_id"=>$category_id]);
                        break;
                    case 3:
                        $jump_url = addon_url('treaty/index/getPdfInfo',["code"=>$code]);
                        break;
                }
                if(!$treaty_find["check_login"] && !$treaty_find['check_repeat']){
//                    Cache::set($cacheName, true);
                }
                $this->success("提交成功", $jump_url);
            }
        }
        $treaty_id = $this->request->get("treaty_id");
        if (!$treaty_id) {
            $this->error("请创建协议", "/");
        }
        $treaty_info = Category::find($treaty_id);
        if (!$treaty_info) {
            $this->error("该协议不存在", "/");
        }
        if($treaty_info["check_login"] && !$this->auth->isLogin()){
            $this->error("请登入后提交", "/index/user/login");
        }
        if($treaty_info["signature"]){
            $signature = json_decode($treaty_info["signature"], true);
            foreach ($signature as $key => $value){
                $treaty_info["content"] = str_replace("【".$key."】", "<span style='color:red;' class='signClass xes sign_".$key."' data-tag_name='".$key."'>点击签字</span>",$treaty_info["content"]);
            }
        }

        //处理公章忽略展示
        $treaty_info["content"] = str_replace("【gongzhang】", "",$treaty_info["content"]);

        $this->assign("title", $treaty_info["name"]);
        $this->assign("treaty_info", $treaty_info);
        return $this->view->fetch();
    }


    public function getPdfInfo(){
        $code = $this->request->get("code");
        //判断是否存在
        $treaty_info = TreatyModel::get(['code'=>$code]);
        if (!$treaty_info) {
            $this->error("协议不存在");
        }
        $treaty_category = Category::get($treaty_info["category_id"]);
        TreatyModel::exportPdf($treaty_info, $treaty_category);
    }



    public function upload()
    {
        $image_data = $this->request->post("image_data");
        $image_data = rawurldecode($image_data);
        $res = $this->upload64Img($image_data);
        if ($res["status"] == 1) {
            $this->success("确认成功", '', $res);
        } else {
            $this->error($res['msg']);
        }
    }


    function upload64Img($pic)
    {
        //header("Content-Type: text/html;charset=utf-8");
        $base64_img = str_replace(' ', '+', $pic);
        $time = date('Ymd', time());
        $up_dir = ROOT_PATH . '/public/uploads/' . $time;
        if (!file_exists($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_img, $result)) {
            $type = $result[2];

            if (in_array($type, array('pjpeg', 'jpeg', 'jpg', 'gif', 'bmp', 'png'))) {
                $name = md5(time()) . "." . $type;
                $new_file = $up_dir . '/' . $name;

                if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_img)))) {
                    $filename = '/uploads/' . $time . '/' . $name;
                    // echo $new_file;
                    $res = explode('/', $new_file);
                    // dump($res);
                    $da['name'] = $res[3];
                    $da['ext'] = $type;
                    $da['type'] = 'img';
                    $da['savename'] = $res[3];
                    $da['savepath'] = 'photo/' . $res[2] . '/';
                    $arr = array('status' => 1, 'msg' => "图片上传成功", 'url' => $filename);
                    return $arr;
                }
                $arr = array('status' => 2, 'msg' => "图片上传失败", 'url' => "");
                return $arr;

            }
            //文件类型错误
            $arr = array('status' => 4, 'msg' => "文件类型错误", 'url' => "");
            return $arr;
        }
        //文件错误
        $arr = array('status' => 3, 'msg' => "文件错误", 'url' => "");
        return $arr;
    }


}
