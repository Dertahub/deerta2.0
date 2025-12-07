<?php

namespace addons\ajcaptcha\controller;

use addons\ajcaptcha\library\Service;
use think\addons\Controller;

use think\Validate;

class Captcha extends Controller
{

    public function get()
    {
        try {
            $service = Service::getCaptchaService();
            $data = $service->get();
        } catch (\Exception $e) {
            return $this->hError($e->getMessage());
        }
        return $this->hSuccess($data);
    }

    public function check()
    {
        $data = request()->param();
        try {
            $rules = [
                'token'     => ['require'],
                'pointJson' => ['require']
            ];
            $validate = new Validate($rules);
            $result = $validate->check($data);
            if (!$result) {
                throw new \Exception($validate->getError());
            }

            $service = Service::getCaptchaService();
            $service->check($data['token'], $data['pointJson']);
        } catch (\Exception $e) {
            return $this->hError($e->getMessage());
        }
        return $this->hSuccess([]);
    }

    protected function hSuccess($data)
    {
        $response = [
            'error'   => false,
            'repCode' => '0000',
            'repData' => $data,
            'repMsg'  => null,
            'success' => true,
        ];
        return json($response);
    }

    protected function hError($msg)
    {
        $response = [
            'error'   => true,
            'repCode' => '6111',
            'repData' => null,
            'repMsg'  => $msg,
            'success' => false,
        ];
        return json($response);
    }

}
