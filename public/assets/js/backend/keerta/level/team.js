define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/level/team/index' + location.search,
                    add_url: 'keerta/level/team/add',
                    edit_url: 'keerta/level/team/edit',
                    del_url: '',
                    multi_url: 'keerta/level/team/multi',
                    import_url: 'keerta/level/team/import',
                    table: 'keerta_level_team',
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
                        {field: 'reward', title: __('Reward'), operate:'BETWEEN'},
                        {field: 'direct_people', title: __('Direct_people')},
                        {field: 'team_people', title: __('Team_people')},
                        {field: 'total_building', title: __('Total_building')},
                        {field: 'one_rate', title: __('One_rate'), operate:'BETWEEN'},
                        {field: 'two_rate', title: __('Two_rate'), operate:'BETWEEN'},
                        {field: 'three_rate', title: __('Three_rate'), operate:'BETWEEN'},
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
