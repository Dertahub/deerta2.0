<?php

namespace app\common\validate;

use think\Validate;

class RealnameValidate extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'surname' => 'require|chs',
        'idcard' => 'require',
        'idcard_image' => 'require',
        'idcard_image2' => 'require',
        'signature' => 'require',
    ];

    /**
     * 字段描述
     */
    protected $field = [
    ];
    /**
     * 提示消息
     */
    protected $message = [
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'submit' => ['surname','idcard','idcard_image','idcard_image2','signature'],
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'surname' => __('姓名'),
            'idcard' => __('身份证号'),
            'idcard_image' => __('身份证人像面'),
            'idcard_image2' => __('身份证国徽面'),
            'signature' => __('签名'),
        ];
        parent::__construct($rules, $message, $field);
    }

}
