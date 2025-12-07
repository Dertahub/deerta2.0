define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/score/order/index' + location.search,
                    add_url: 'keerta/score/order/add',
                    edit_url: 'keerta/score/order/edit',
                    del_url: 'keerta/score/order/del',
                    multi_url: 'keerta/score/order/multi',
                    import_url: 'keerta/score/order/import',
                    table: 'keerta_score_order',
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
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'order_sn', title: __('Order_sn'), operate: 'LIKE'},
                        // {field: 'goods_id', title: __('Goods_id')},
                        {field: 'goods_name', title: __('Goods_name'), operate: 'LIKE'},
                        // {field: 'score', title: __('Score')},
                        {field: 'num', title: __('Num')},
                        {field: 'order_status', title: __('Order_status'), searchList: {"1":__('Order_status 1'),"2":__('Order_status 2'),"3":__('Order_status 3')}, formatter: Table.api.formatter.status},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'receiver_name', title: __('Receiver_name'), operate: 'LIKE'},
                        // {field: 'deliverytime', title: __('Deliverytime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'delivery_sn', title: __('Delivery_sn'), operate: 'LIKE'},
                        // {field: 'delivery_company', title: __('Delivery_company'), operate: 'LIKE'},
                        {field: 'receiver_address', title: __('Receiver_address'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
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
