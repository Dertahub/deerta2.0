define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/realname/index' + location.search,
                    add_url: 'keerta/realname/add',
                    edit_url: 'keerta/realname/edit',
                    del_url: 'keerta/realname/del',
                    multi_url: 'keerta/realname/multi',
                    import_url: 'keerta/realname/import',
                    table: 'keerta_realname',
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
                        // {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('User_id')},
                        // {field: 'user.nickname', title: __('User.nickname'), operate: 'LIKE'},
                        {field: 'surname', title: __('Surname'), operate: 'LIKE'},
                        {field: 'idcard', title: __('Idcard'), operate: 'LIKE'},
                        {field: 'idcard_image', title: __('Idcard_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'idcard_image2', title: __('Idcard_image2'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'signature', title: __('Signature'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            buttons: [
                                {
                                    name: 'edit',
                                    text: __('审核'),
                                    title: __('审核'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-legal',
                                    url:'keerta/realname/edit',
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return row.status == 0;
                                    }
                                },{
                                    name: 'detail',
                                    text: __('详情'),
                                    title: __('详情'),
                                    classname: 'btn btn-xs btn-default btn-dialog',
                                    icon: 'fa fa-eye',
                                    url:'keerta/realname/edit',
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return row.status != 0;
                                    }
                                },
                            ],
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        }
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
