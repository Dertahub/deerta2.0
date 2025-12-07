define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/level/level/index' + location.search,
                    add_url: 'keerta/level/level/add',
                    edit_url: 'keerta/level/level/edit',
                    del_url: '',
                    multi_url: 'keerta/level/level/multi',
                    import_url: 'keerta/level/level/import',
                    table: 'keerta_level',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'asc',
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'id', title: __('等级ID')},
                        {field: 'level_name', title: __('Level_name'), operate: 'LIKE'},
                        {field: 'level_logo', title: __('等级图标'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'score', title: __('Score')},
                        {field: 'interest_rate', title: __('Interest_rate'), operate:'BETWEEN'},
                        {field: 'small_rate', title: __('Small_rate'), operate:'BETWEEN'},
                        {field: 'sign_reward', title: __('Sign_reward'), operate:'BETWEEN'},
                        // {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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
