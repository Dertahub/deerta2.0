<?php

namespace addons\ajcaptcha\library;

use Fastknife\Service\BlockPuzzleCaptchaService;
use Fastknife\Service\ClickWordCaptchaService;

class Service
{

    public static function getCaptchaService($captchaType = null)
    {
        if (is_null($captchaType)) {
            $config = get_addon_config('ajcaptcha');
            $captchaType = $config['captchaType'] ?: 'blockPuzzle';
        }
        $config = config('captcha');
        switch ($captchaType) {
            case "clickWord":
                $service = new ClickWordCaptchaService($config);
                break;
            case "blockPuzzle":
                $service = new BlockPuzzleCaptchaService($config);
                break;
            default:
                throw new \Exception('captchaType参数不正确！');
        }
        return $service;
    }
}