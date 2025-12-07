<?php

namespace app\common\validate;

use think\Validate;

class IndexValidate extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'id' => 'require|integer|>:0',
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
        'id' => ['id'],
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'id' => __('ID'),
        ];
        parent::__construct($rules, $message, $field);
    }

}
