define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'keerta/treaty/sign/index' + location.search,
                    add_url: 'keerta/treaty/sign/add',
                    edit_url: 'keerta/treaty/sign/edit',
                    del_url: 'keerta/treaty/sign/del',
                    multi_url: 'keerta/treaty/sign/multi',
                    import_url: 'keerta/treaty/sign/import',
                    table: 'keerta_treaty_sign',
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
                        {field: 'order_sn', title: __('Order_sn'), operate: 'LIKE'},
                        // {field: 'content', title: __('Content')},
                        // {field: 'official_seal_image', title: __('Official_seal_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'official_seal_image2', title: __('Official_seal_image2'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            /*buttons: [
                                {
                                    name: 'export_pdf',
                                    text: __('导出PDF'),
                                    title: __('下载页面'),
                                    classname: 'btn btn-xs btn-success  btn-dialog',
                                    url:function(row){
                                        return 'keerta/treaty/sign/export_pdf?ids='+row.id;
                                    }
                                }
                            ]*/
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
