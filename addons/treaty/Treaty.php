<?php

namespace addons\treaty;

use app\common\library\Menu;
use think\Addons;

/**
 * 插件
 */
class Treaty extends Addons
{

    /**
     * 应用初始化
     */
    public function appInit()
    {
        if (!class_exists('\PhpOffice\PhpWord\PhpWord')) {
            \think\Loader::addNamespace('PhpOffice\PhpWord', ADDON_PATH . 'treaty' . DS . 'library' . DS . 'PhpWord' . DS);
        }
        if (!class_exists('\PhpOffice\Common\Text')) {
            \think\Loader::addNamespace('PhpOffice\Common', ADDON_PATH . 'treaty' . DS . 'library' . DS . 'Common' . DS);
        }
    }

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {

        $menu = [
            [
                'name'    => 'treaty',
                'title'   => '协议管理',
                'icon'    => 'fa fa-magic',
                'sublist' => [
                    [
                        'name'    => 'treaty/category',
                        'title'   => '协议',
                        'icon'    => 'fa fa-table',
                        'sublist' => [
                            ['name' => 'treaty/category/add', 'title' => '添加'],
                            ['name' => 'treaty/category/index', 'title' => '查看'],
                            ['name' => 'treaty/category/edit', 'title' => '详情'],
                            ['name' => 'treaty/category/del', 'title' => '删除'],
                        ]
                    ],
                    [
                        'name'    => 'treaty/info',
                        'title'   => '协议详情',
                        'icon'    => 'fa fa-table',
                        'sublist' => [
                            ['name' => 'treaty/info/add', 'title' => '添加'],
                            ['name' => 'treaty/info/index', 'title' => '查看'],
                            ['name' => 'treaty/info/edit', 'title' => '详情'],
                            ['name' => 'treaty/info/del', 'title' => '删除'],
                            ['name' => 'treaty/info/export_pdf', 'title' => '导出Pdf'],
                        ]
                    ],
                ]
            ]
        ];
        Menu::create($menu);
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {

        Menu::delete('treaty');
        return true;
    }

    /**
     * 插件启用方法
     * @return bool
     */
    public function enable()
    {
        Menu::enable('treaty');
        return true;
    }

    /**
     * 插件禁用方法
     * @return bool
     */
    public function disable()
    {
        Menu::disable('treaty');
        return true;
    }

}
