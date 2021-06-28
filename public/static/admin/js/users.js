define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'users/index',
        add_url: 'users/add',
        edit_url: 'users/edit',
        delete_url: 'users/delete',
        export_url: 'users/export',
        modify_url: 'users/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh', 'export'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID', width: 80, hide: false},
                    {field: 'fid', title: '推荐上级'},
                    {field: 'address', title: '钱包地址'},
                    {field: 'amount1', title: '可用账户', search: false},
                    {field: 'amount2', title: '不可用账户', search: false},
                    {field: 'amount3', title: '奖金账户', search: false},
                    {field: 'quota', title: '额度', search: false},
                    {field: 'release_ratio', title: '独立释放比例'},
                    {field: 'level', title: '管理奖等级'},
                    {field: 'fl_time', title: '上次返利时间', search: false},
                    {field: 'create_time', title: '注册时间', search: 'range'},
                    {
                        width: 120,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '编辑',
                                url: init.edit_url,
                                method: 'open',
                                auth: 'edit',
                                class: 'layui-btn layui-btn-xs layui-btn-success',
                            }]
                        ]
                    }

                ]],
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
    };
    return Controller;
});