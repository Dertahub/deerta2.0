define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/order/order/index' + location.search,
                    add_url: 'keerta/order/order/add',
                    edit_url: '',
                    del_url: 'keerta/order/order/del',
                    multi_url: 'keerta/order/order/multi',
                    import_url: 'keerta/order/order/import',
                    table: 'keerta_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'refer_ids', title: __('输入ID搜索伞下'),visible:false},
                        {field: 'mark', title: __('标记'), searchList: {"0":__('否'),"1":__('是')}, operate: false, formatter: Table.api.formatter.label},
                        {field: 'goods_id', title: __('Goods_id')},
                        {field: 'amount', title: __('Amount'), operate:'false'},
                        {field: 'goods_name', title: __('Goods_name'), operate: 'LIKE'},
                        {field: 'bouns_rate', title: __('Bouns_rate'), operate:false},
                        {field: 'red_envelope_amount', title: __('Red_envelope_amount'), operate:false},
                        {field: 'day_profit_rate', title: __('Day_profit_rate'), operate:false},
                        {field: 'interest_rate', title: __('Interest_rate'), operate:false},
                        {field: 'start_cycle', title: __('Start_cycle'), operate:false},
                        {field: 'interest_days', title: __('Interest_days'), operate:false},
                        {field: 'interest_num', title: __('Interest_num'), operate:false},
                        {field: 'interest_aomunt', title: __('Interest_aomunt'), operate:false},
                        {field: 'bouns_amount', title: __('Bouns_amount'), operate:false},
                        {field: 'interest_total_amount', title: __('Interest_total_amount'), operate:false},
                        {field: 'score', title: __('Score'), operate:'false'},
                        {field: 'handwritten_signature', title: __('签名'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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
