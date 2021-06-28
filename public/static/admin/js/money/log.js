define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'money.log/index',
        export_url: 'money.log/export',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh','export'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id', width: 100, search: false, hide: true},
                    {field: 'uid', title: '账户ID'},
                    {field: 'address', title: '账户', search: false},
                    {field: 'amount', title: '金额'},
                    {field: 'content', title: '明细详情', search: false},
                    {field: 'create_time', title: '创建时间', search: 'range'},
                ]],
            });

            ea.listen();
        },
    };
    return Controller;
});