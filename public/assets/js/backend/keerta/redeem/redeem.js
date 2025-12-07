define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/redeem/redeem/index' + location.search,
                    add_url: 'keerta/redeem/redeem/add',
                    edit_url: '',
                    del_url: 'keerta/redeem/redeem/del',
                    multi_url: 'keerta/redeem/redeem/multi',
                    import_url: 'keerta/redeem/redeem/import',
                    table: 'keerta_redeem',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                sortOrder: 'desc',
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'id', title: __('记录ID')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'from', title: __('使用币种'), searchList: {"money":__('From money'),"usdt":__('From usdt'),"withdraw_money":__('From withdraw_money'),"withdraw_usdt":__('From withdraw_usdt')}, formatter: Table.api.formatter.normal},
                        {field: 'to', title: __('To'), searchList: {"money":__('To money'),"usdt":__('To usdt'),"withdraw_money":__('To withdraw_money'),"withdraw_usdt":__('To withdraw_usdt')}, formatter: Table.api.formatter.normal},
                        {field: 'money', title: __('使用金额')},
                        {field: 'to_money', title: __('兑换金额')},
                        {field: 'hl', title: __('Hl'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'memo', title: __('Memo'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        // {field: 'reason', title: __('Reason'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
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
