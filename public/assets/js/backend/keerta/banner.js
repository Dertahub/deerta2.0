define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/banner/index' + location.search,
                    add_url: 'keerta/banner/add',
                    edit_url: 'keerta/banner/edit',
                    del_url: 'keerta/banner/del',
                    multi_url: 'keerta/banner/multi',
                    import_url: 'keerta/banner/import',
                    table: 'keerta_banner',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        // {field: 'title', title: __('Title'), operate: 'LIKE'},
                        {field: 'seat', title: __('Seat'), searchList: {"1":__('Seat 1'),"2":__('Seat 2'),"3":__('Seat 3'),"4":__('Seat 4')}, formatter: Table.api.formatter.normal},
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2')}, formatter: Table.api.formatter.normal},
                        // {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'video', title: __('Video'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'switch', title: __('Switch'), searchList: {"1":__('Switch 1'),"0":__('Switch 0')}, table: table, formatter: Table.api.formatter.toggle},
                        {field: 'jump_method', title: __('跳转方式'), searchList: {"1":__('不跳转'),"2":__('外链')}, formatter: Table.api.formatter.label},
                        {field: 'weigh', title: __('Weigh'), operate: false},
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
