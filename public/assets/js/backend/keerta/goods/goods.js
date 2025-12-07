define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/goods/goods/index' + location.search,
                    add_url: 'keerta/goods/goods/add',
                    edit_url: 'keerta/goods/goods/edit',
                    del_url: 'keerta/goods/goods/del',
                    multi_url: 'keerta/goods/goods/multi',
                    import_url: 'keerta/goods/goods/import',
                    table: 'keerta_goods',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                // fixedColumns: true,
                // fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'goodscate.name', title: __('所属分类'), operate: 'LIKE'},
                        {field: 'goodstype.name', title: __('投资类型'), operate: 'LIKE'},
                        {field: 'goods_name', title: __('Goods_name'), operate: 'LIKE'},
                        {field: 'total_amount', title: __('Total_amount')},
                        {field: 'surplus_amount', title: __('剩余可投金额')},
                        {field: 'limit_num', title: __('Limit_num')},
                        {field: 'start_amount', title: __('Start_amount')},
                        {field: 'start_cycle', title: __('Start_cycle')},
                        // {field: 'bouns_rate', title: __('Bouns_rate'), operate:'BETWEEN'},
                        // {field: 'red_envelope_amount', title: __('Red_envelope_amount'), operate:'BETWEEN'},
                        // {field: 'day_profit_rate', title: __('Day_profit_rate'), operate:'BETWEEN'},
                        // {field: 'single_amount', title: __('Single_amount')},
                        // {field: 'level_ids', title: __('Level_ids'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        // {field: 'kpis_image', title: __('Kpis_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'surplus_amount', title: __('Surplus_amount')},
                        // {field: 'guarantee_company_image', title: __('Guarantee_company_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'interest_days', title: __('Interest_days')},
                        {field: 'is_hot', title: __('Is_hot'), searchList: {"0":__('Is_hot 0'),"1":__('Is_hot 1')}, table: table, formatter: Table.api.formatter.toggle},
                        {field: 'switch', title: __('Switch'), searchList: {"1":__('Switch 1'),"0":__('Switch 0')}, table: table, formatter: Table.api.formatter.toggle},
                        {field: 'weigh', title: __('Weigh'), operate: false},

                        // {field: 'level.id', title: __('Level.id')},
                        // {field: 'level.level_name', title: __('Level.level_name'), operate: 'LIKE'},
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
