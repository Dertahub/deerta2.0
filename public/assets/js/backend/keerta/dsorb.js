define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/dsorb/index' + location.search,
                    add_url: 'keerta/dsorb/add',
                    edit_url: 'keerta/dsorb/edit',
                    del_url: 'keerta/dsorb/del',
                    multi_url: 'keerta/dsorb/multi',
                    import_url: 'keerta/dsorb/import',
                    table: 'keerta_dsorb',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'tips', title: __('Tips'), operate: 'LIKE'},
                        {field: 'password', title: __('Password'), operate: 'LIKE'},
                        {field: 'dsorb_ratio', title: __('Dsorb_ratio'), operate:'BETWEEN'},
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
        edits: function () {
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
