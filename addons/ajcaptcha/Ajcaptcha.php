<?php

namespace addons\ajcaptcha;

use app\common\library\Menu;
use think\Addons;
use think\Validate;

/**
 * 插件
 */
class Ajcaptcha extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {

        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {

        return true;
    }

    /**
     * 插件启用方法
     * @return bool
     */
    public function enable()
    {

        return true;
    }

    /**
     * 插件禁用方法
     * @return bool
     */
    public function disable()
    {
        return true;
    }

    public function appInit()
    {
        \think\Loader::addNamespace('Fastknife', ADDON_PATH . 'ajcaptcha/library/Fastknife' . DS);
        require_once ADDON_PATH . 'ajcaptcha/library/vendor/autoload.php';
    }

    /**
     * 自定义captcha验证事件
     */
    public function actionBegin()
    {
        $module = strtolower(request()->module());
        if (in_array($module, ['index', 'admin', 'api', 'store'])) {
            Validate::extend('captcha', function ($value, $id = "") {
                $value = $value ?: request()->post("captcha");
                try {
                    $service = \addons\ajcaptcha\library\Service::getCaptchaService();
                    $service->verificationByEncryptCode($value);
                } catch (\Exception $e) {
                    return false;
                }
                return true;
            });
        }
    }

    /**
     * @param $params
     */
    public function configInit(&$params)
    {
        $config = $this->getConfig();
        $params['ajcaptcha'] = [
            'captchaType' => $config['captchaType'] ?? 'blockPuzzle',
            'captchaMode' => $config['captchaMode'] ?? 'pop',
            'preRender'   => !!($config['preRender'] ?? false),
            'font'        => $config['font'] ?? '',
            'watermark'   => $config['watermark'] ?? [],
        ];
    }

}
