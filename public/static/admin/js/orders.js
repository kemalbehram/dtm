define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'orders/index',
        // delete_url: 'orders/delete',
        export_url: 'orders/export',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh', 'export'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID', width: 100, search: false, hide: true},
                    {field: 'uid', title: '用户ID', width: 100},
                    {field: 'address', title: '钱包地址'},
                    {field: 'amount', title: '质押数量(DTM)'},
                    {field: 'types', title: '质押期限', selectList:{0:'异常', 1:'1天', 7:'7天', 15:'15天', 30:'30天'}},
                    {field: 'finish', title: '已质押天数', search: false, templet: function (d) {
                            return '<div class="layui-table-cell laytable-cell-1-0-6">'+ d.finish +'天</div>';
                        }},
                    {field: 'fl_amount', title: '已发放利息', search: false, templet: function (d) {
                            return '<div class="layui-table-cell laytable-cell-1-0-7">'+ d.fl_amount +' DTM</div>';
                        }},
                    {field: 'create_time', title: '质押时间', search: 'range'},
                    // {
                    //     width: 100,
                    //     title: '操作',
                    //     templet: ea.table.tool,
                    //     operat: [
                    //         [{
                    //             text: '删除',
                    //             title: '确定删除？',
                    //             url: init.delete_url,
                    //             method: 'request',
                    //             auth: 'delete',
                    //             class: 'layui-btn layui-btn-xs layui-btn-danger',
                    //         }]
                    //     ]
                    // }
                ]],
            });

            ea.listen();
        },
    };
    return Controller;
});