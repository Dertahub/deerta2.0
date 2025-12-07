<?php

namespace app\common\validate;

use think\Validate;

class AccountValidate extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'bank_card' => 'require|integer',
        'bank_name' => 'require',
        'bank_deposit' => 'require',
        'pay_password' => 'require|length:6',
        'usdt_address' => 'require',
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
        'bankAdd' => ['bank_card', 'bank_name', 'bank_deposit', 'pay_password'],
        'usdtAdd' => ['usdt_address', 'pay_password'],
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'bank_card' => __('银行卡号'),
            'bank_name' => __('银行名称'),
            'bank_deposit' => __('开户行'),
            'pay_password' => __('支付密码'),
            'usdt_address' => __('USDT地址'),
        ];
        parent::__construct($rules, $message, $field);
    }

}
