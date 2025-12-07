define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/recharge/usdt/index' + location.search,
                    add_url: 'keerta/recharge/usdt/add',
                    edit_url: 'keerta/recharge/usdt/edit',
                    del_url: 'keerta/recharge/usdt/del',
                    multi_url: 'keerta/recharge/usdt/multi',
                    import_url: 'keerta/recharge/usdt/import',
                    table: 'keerta_usdt',
                }
            });

            var table = $("#table");
            //当表格数据加载完成时
            table.on('load-success.bs.table', function (e, data) {
                // console.log(data);
                //这里我们手动设置底部的值
                $("#total_num").text(data.first_count);
                $("#total_money").text(data.total_money);

                $("#total_num2").text(data.first_count2);
                $("#total_money2").text(data.total_money2);
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                sortOrder: 'desc',
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'realname', title: __('实名'), operate: false},
                        {field: 'mark', title: __('标记'), searchList: {"0":__('否'),"1":__('是')}, operate: false, formatter: Table.api.formatter.label},
                        {field: 'refer_ids', title: __('输入ID搜索伞下'),visible:false},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table, events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'edit',
                                    text: '审核',// 方法一
                                    title: '审核', // 方法二
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-edit',
                                    url: 'keerta/recharge/usdt/edit',
                                    visible: function (row) {
                                        return row.status == 0;
                                    }
                                },{
                                    name: 'edit',
                                    text: '详情',// 方法一
                                    title: '详情', // 方法二
                                    classname: 'btn btn-xs btn-default btn-dialog',
                                    icon: 'fa fa-eye',
                                    url: 'keerta/recharge/usdt/edit',
                                    visible: function (row) {
                                        return row.status != 0;
                                    }
                                },
                            ],
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
