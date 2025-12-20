define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/withdraw/cny/index' + location.search,
                    add_url: 'keerta/withdraw/cny/add',
                    edit_url: 'keerta/withdraw/cny/edit',
                    del_url: 'keerta/withdraw/cny/del',
                    multi_url: 'keerta/withdraw/cny/multi',
                    import_url: 'keerta/withdraw/cny/import',
                    table: 'keerta_withdraw_cny',
                }
            });

            var table = $("#table");
            //当表格数据加载完成时
            table.on('load-success.bs.table', function (e, data) {
                // console.log(data);
                //这里我们手动设置底部的值
                $("#total_num").text(data.total_count);
                $("#total_money").text(data.total_money);
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'realname', title: __('实名'), operate: false},
                        {field: 'mark', title: __('标记'), searchList: {"0":__('否'),"1":__('是')},  operate: false,formatter: Table.api.formatter.label},
                        {field: 'refer_ids', title: __('输入ID搜索伞下'),visible:false},
                        {field: 'bank_card', title: __('Bank_card'), operate: 'LIKE'},
                        {field: 'bank_name', title: __('Bank_name'), operate: 'LIKE'},
                        {field: 'bank_deposit', title: __('Bank_deposit'), operate: 'LIKE'},
                        {field: 'money', title: __('金额'), operate: 'LIKE'},
                        {field: 'fee', title: __('手续费'), operate: 'LIKE'},
                        {field: 'actual_money', title: __('实际到账金额'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('申请时间'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'actual_time', title: __('操作时间'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        /*{
                            field: 'updatetime',
                            title: __('操作时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: function(value, row, index, field) {
                                // 先检查 status 是否存在，再判断是否为 0
                                if (typeof row.status !== 'undefined' && row.status == 0) {
                                    return '';
                                }
                                // 如果值为空也直接返回空
                                if (!value) {
                                    return '';
                                }
                                return Table.api.formatter.datetime.call(this, value, row, index, field);
                            }
                        },*/
                        // {field: 'reason', title: __('Reason'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'edit',
                                    text: __('审核'),
                                    title: __('审核'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-legal',
                                    url:'keerta/withdraw/cny/edit',
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
                                    url:'keerta/withdraw/cny/edit',
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
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
