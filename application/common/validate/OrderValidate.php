<?php

namespace app\common\validate;

use think\Validate;

class OrderValidate extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'goods_id' => 'require|integer|>:0',
        'amount' => 'require|float|>:0',
        'pay_password' => 'require',
        'handwritten_signature' => 'require',
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
        'pay' => ['goods_id', 'amount', 'pay_password','handwritten_signature'],
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'goods_id' => __('产品ID'),
            'amount' => __('金额'),
            'pay_password' => __('支付密码'),
            'handwritten_signature' => __('手写签名'),
        ];
        parent::__construct($rules, $message, $field);
    }

}
