<?php

namespace app\common\validate;

use think\Validate;

class UserValidate extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'email' => 'require|email',
        'mobile' => 'require|number',
        'password' => 'require|length:6,30',
        'confirm_password' => 'require|length:6,30',
        'pay_password' => 'require|length:6',
        'confirm_pay_password' => 'require|length:6|confirm:pay_password',
        'invite_code' => 'require|length:8',
        'device_id' => 'require',
        'country_code' => 'require',
        'type' => 'require|in:password,pay_password',
        'old_password' => 'require|length:6,30',
        'new_password' => 'require|length:6,30',
        'surname' => 'require|chs',
        'idcard' => 'require|length:15,18',
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
        'mobile.regex' => '请输入正确的手机号',
        'invite_code.length' => '邀请码错误！',
        'country_code.require' => '请选择一个国家！',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'mobileRegister' => ['mobile', 'password','confirm_password', 'pay_password', 'confirm_pay_password','invite_code', 'device_id','country_code'],
        'emailRegister' => ['email', 'password', 'confirm_password','pay_password', 'confirm_pay_password','invite_code', 'device_id'],
        'mobileLogin' => ['mobile', 'password'],
        'emailLogin' => ['email', 'password'],
        'changePassword' => ['type', 'old_password', 'new_password', 'confirm_password', 'surname', 'idcard'],
        'forgetPassword' => ['type', 'new_password', 'confirm_password', 'surname', 'idcard'],
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'email' => __('邮箱'),
            'mobile' => __('手机号'),
            'password' => __('密码'),
            'pay_password' => __('支付密码'),
            'invite_code' => __('邀请码'),
            'device_id' => __('设备唯一标识'),
            'country_code' => __('请选择一个国家'),
            'type' => __('密码类型'),
            'old_password' => __('旧密码'),
            'new_password' => __('新密码'),
            'confirm_password' => __('确认密码'),
            'surname' => __('姓名'),
            'idcard' => __('身份证号'),
            'confirm_pay_password' => __('确认支付密码'),
        ];
        parent::__construct($rules, $message, $field);
    }

}
