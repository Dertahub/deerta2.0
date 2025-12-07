define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'treaty/info/index' + location.search,
                    add_url: 'treaty/info/add',
                    edit_url: 'treaty/info/edit',
                    del_url: 'treaty/info/del',
                    multi_url: 'treaty/info/multi',
                    table: 'treaty_info',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                exportOptions: {
                    ignoreColumn: [0,6,8, 'operate'] //默认不导出第一列(checkbox)与操作(operate)列
                },
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'treatycategory.name', title: __('Treatycategory.name'),formatter: Table.api.formatter.search},
                        {field: 'user.nickname', title: __('Nickname'), operate: 'LIKE'},
                        // {field: 'category_id', title: __('Category_id')},
                        // {field: 'name', title: __('Name'), operate:'LIKE'},
                        // {field: 'phone', title: __('Phone'), operate:'LIKE'},
                        // {field: 'id_card', title: __('Id_card'), operate:'LIKE'},
                        // {field: 'image', title: __('Image'),formatter: Table.api.formatter.url,operate:false},
                        // {field: 'description', title: __('Description')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'weigh', title: __('Weigh'),operate:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'export_pdf',
                                    text: __('导出PDF'),
                                    title: __('下载页面'),
                                    classname: 'btn btn-xs btn-success  btn-dialog',
                                    url:function(row){
                                        return 'treaty/info/export_pdf?ids='+row.id;
                                    }
                                }
                            ],}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            $(".btn-clear").click(function () {
                Fast.api.ajax('treaty/info/clear');
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        export_word: function () {
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