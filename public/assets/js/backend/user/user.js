define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'user.createtime',
                fixedColumns: true,
                fixedRightNumber: 1,
                sortName: 'createtime',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        // {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'realname', title: __('实名'), operate: false},
                        {field: 'refer_ids', title: __('输入ID搜索伞下'),visible:false},
                        {field: 'email', title: __('Email'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'level_id', title: __('会员等级'), operate: 'LIKE'},
                        {field: 'team_id', title: __('团队等级'), operate: 'LIKE'},
                        {field: 'refer', title: __('上级ID'), operate: 'LIKE'},
                        {field: 'invite_code', title: __('邀请码'), operate: 'LIKE'},
                        {field: 'mark', title: __('标记'), searchList: {"1":__('Switch 1'),"0":__('Switch 0')}, table: table, formatter: Table.api.formatter.toggle},
                        {field: 'score', title: __('Score'), operate: 'BETWEEN', sortable: true},
                        {field: 'money', title: __('充值钱包余额'), operate: 'BETWEEN', sortable: true},
                        {field: 'usdt', title: __('充值钱包USDT余额'), operate: 'BETWEEN', sortable: true},
                        {field: 'withdraw_money', title: __('提现钱包余额'), operate: 'BETWEEN', sortable: true},
                        {field: 'withdraw_usdt', title: __('提现钱包USDT余额'), operate: 'BETWEEN', sortable: true},
                        {field: 'dsorb_money', title: __('释放钱包余额'), operate: 'BETWEEN', sortable: true},
                        {field: 'logintime', title: __('Logintime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'loginip', title: __('Loginip'), formatter: Table.api.formatter.search},
                        {field: 'jointime', title: __('Jointime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'joinip', title: __('Joinip'), formatter: Table.api.formatter.search},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {normal: __('Normal'), hidden: __('Hidden')}},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});