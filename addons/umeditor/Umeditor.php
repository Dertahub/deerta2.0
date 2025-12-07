<?php

namespace addons\umeditor;

use think\Addons;

/**
 * 插件
 */
class Umeditor extends Addons
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
     * @param $params
     */
    public function configInit(&$params)
    {
        $config = $this->getConfig();
        $params['umeditor'] = [
            'classname'          => $config['classname'] ?? '.editor',
            'fullmode'           => !!($config['fullmode'] ?? '1'),
            'autoHeightEnabled'  => !!($config['autoHeightEnabled'] ?? '1'),
            'minFrameHeight'     => is_numeric($config['minFrameHeight']) ? intval($config['minFrameHeight']) : $config['minFrameHeight'],
            'initialFrameHeight' => is_numeric($config['initialFrameHeight']) ? intval($config['initialFrameHeight']) : $config['initialFrameHeight'],
            'toolbar'            => $config['toolbar'],
            'isdompurify'        => !!($config['isdompurify'] ?? '0'),
            'allowiframeprefixs' => $config['allowiframeprefixs'] ?? [],
            'baidumapkey'        => $config['baidumapkey'] ?? '',
            'baidumapcenter'     => $config['baidumapcenter'] ?? ''
        ];
    }

}
