define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'recharge/index',
        add_url: 'recharge/add',
        edit_url: 'recharge/edit',
        delete_url: 'recharge/delete',
        export_url: 'recharge/export',
        modify_url: 'recharge/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh', 'export'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id', width: 100, search: false, hide: true},
                    {field: 'tx', title: 'TX', templet: function (d){
                            return '<a style="text-decoration: underline;" href="https://tronscan.org/#/transaction/'+ d.tx +'" class="layui-table-cell" target="_blank">'+ d.tx +'</a>';
                        }
                    },
                    {field: 'from_address', title: '用户地址',width: 340},
                    {field: 'to_address', title: '收款地址',width: 200},
                    {field: 'amount', title: '充值数量(BUSD)'},
                    {field: 'status', title: '扫描状态', selectList:{0:'未扫描', 1:'已扫描'}},
                    {field: 'state', title: '入账状态', selectList:{0:'未入账', 1:'已入账'}},
                    {field: 'block_time', title: '区块确认时间', search: 'range', width: 180},
                    {field: 'create_time', title: '添加时间', search: false, hide: true},

                ]],
            });

            ea.listen();
        },
    };
    return Controller;
});