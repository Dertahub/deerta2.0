define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/noticecate/index' + location.search,
                    add_url: 'keerta/noticecate/add',
                    edit_url: 'keerta/noticecate/edit',
                    del_url: 'keerta/noticecate/del',
                    multi_url: 'keerta/noticecate/multi',
                    import_url: 'keerta/noticecate/import',
                    table: 'keerta_noticecate',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title'), operate: 'LIKE'},
                        {field: 'switch', title: __('Switch'), searchList: {"1":__('Switch 1'),"0":__('Switch 0')}, table: table, formatter: Table.api.formatter.toggle},
                        {field: 'home', title: __('推荐'), searchList: {"1":__('Switch 1'),"0":__('Switch 0')}, table: table, formatter: Table.api.formatter.toggle},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'weigh', title: __('Weigh'), operate: false},
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
