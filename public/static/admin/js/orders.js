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
                    {field: 'finish', title: '已质押天数', search: false},
                    {field: 'fl_amount', title: '已发放利息', search: false},
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
        // index2: function () {
        //     ea.table.render({
        //         init: {
        //             table_elem: '#currentTable',
        //             table_render_id: 'currentTableRenderId',
        //             index_url: 'orders/index2',
        //         },
        //         toolbar: ['refresh', 'export'],
        //         cols: [[
        //             {type: 'checkbox'},
        //             {field: 'id', title: 'id', width: 100, search: false, hide: true},
        //             {field: 'tx', title: 'TX', templet: function (d){
        //                     return '<a style="text-decoration: underline;" href="https://tronscan.org/#/transaction/'+ d.tx +'" class="layui-table-cell" target="_blank">'+ d.tx +'</a>';
        //                 }
        //             },
        //             {field: 'type', search: 'select', selectList: getTypeList, title: '流通周期', width: 100},
        //             {field: 'from_address', title: '发送人地址',width: 340},
        //             {field: 'to_address', title: '收款地址',width: 200},
        //             {field: 'amount', title: '收款金额'},
        //             {field: 'fl_bl', title: '返利比例', width: 120, search: false, templet: function (d){
        //                     return '<div class="layui-table-cell">'+ d.fl_bl +'%</div>';
        //                 }
        //             },
        //             {field: 'fl_amount', title: '返利金额', search: false},
        //             // {field: 'status', title: '返利状态', selectList: getStatusList, search: false, templet: function (d){
        //             //         return '<div class="layui-table-cell" style="color:red;font-weight:bold;">'+ getStatusList[d.status] +'</div>';
        //             //     }
        //             // },
        //             {field: 'block_time', title: '区块确认时间', search: 'range',width: 180},
        //             {field: 'fl_time', title: '到期时间', search: 'range',width: 180},
        //             {field: 'create_time', title: '添加时间', search: false, hide: true},
        //             {
        //                 width: 150,
        //                 title: '操作',
        //                 templet: ea.table.tool,
        //                 operat: [
        //                     [{
        //                         text: '返利到账',
        //                         title: '即将变更订单状态及结算推荐奖，确定已打款？',
        //                         url: init.arrive_url,
        //                         method: 'request',
        //                         auth: 'arrive',
        //                         class: 'layui-btn layui-btn-xs layui-btn-normal',
        //                     }, {
        //                         text: '删除',
        //                         title: '确定删除？',
        //                         url: init.delete_url,
        //                         method: 'request',
        //                         auth: 'delete',
        //                         class: 'layui-btn layui-btn-xs layui-btn-danger',
        //                     }]
        //                 ]
        //             }
        //         ]],
        //     });
        //
        //     ea.listen();
        // },
    };
    return Controller;
});