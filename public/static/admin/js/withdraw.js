define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'withdraw/index',
        export_url: 'withdraw/export',
        arrive_url: 'withdraw/arrive',
        reject_url: 'withdraw/reject',
        delete_url: 'withdraw/delete',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh', 'delete', 'export'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID', hide: true, search: false},
                    {field: 'address', title: '地址', width: 340},
                    {field: 'amount', title: '申请金额(BUSD)'},
                    {field: 'fee', title: '手续费'},
                    {field: 'real_amount', title: '实际到账'},
                    {field: 'status', search: 'select', selectList: ["待审核","已审核"], title: '状态', templet: function (d){
                            return '<div class="layui-table-cell" style="color:red;font-weight:bold;">待审核</div>';
                        }
                    },
                    {field: 'create_time', title: '申请时间', search: 'range'},
                    {
                        width: 180,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '通过',
                                title: '即将变更订单状态，确定已打款？',
                                url: init.arrive_url,
                                method: 'request',
                                auth: 'arrive',
                                class: 'layui-btn layui-btn-xs layui-btn-normal',
                            },{
                                text: '驳回',
                                title: '驳回将返还金额，是否继续？',
                                url: init.reject_url,
                                method: 'request',
                                auth: 'reject',
                                class: 'layui-btn layui-btn-xs layui-btn-danger',
                            },{
                                text: '删除',
                                title: '确定删除？',
                                url: init.delete_url,
                                method: 'request',
                                auth: 'delete',
                                class: 'layui-btn layui-btn-xs layui-btn-danger',
                            }]
                        ]
                    }

                ]],
            });

            ea.listen();
        },
        index2: function () {
            ea.table.render({
                init: {
                    table_elem: '#currentTable',
                    table_render_id: 'currentTableRenderId',
                    index_url: 'withdraw/index2',
                },
                toolbar: ['refresh', 'export'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID', hide: true, search: false},
                    {field: 'address', title: '地址', width: 340},
                    {field: 'amount', title: '申请金额(BUSD)'},
                    {field: 'fee', title: '手续费'},
                    {field: 'real_amount', title: '实际到账'},
                    {field: 'status', search: 'select', selectList: ["待审核","已审核"], title: '状态', templet: function (d){
                            return '<div class="layui-table-cell" style="color:green;font-weight:bold;">已审核</div>';
                        }
                    },
                    {field: 'content', title: '审核情况', search: false},
                    {field: 'create_time', title: '申请时间', search: 'range'},
                    {field: 'cl_time', title: '处理时间', search: 'range'},
{
                        width: 180,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '删除',
                                title: '确定删除？',
                                url: init.delete_url,
                                method: 'request',
                                auth: 'delete',
                                class: 'layui-btn layui-btn-xs layui-btn-danger',
                            }]
                        ]
                    }
                ]],
            });

            ea.listen();
        },
        arrive: function () {
            ea.listen();
        },
        reject: function () {
            ea.listen();
        }
    };
    return Controller;
});