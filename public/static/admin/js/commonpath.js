define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'commonpath/index',
    };

    var Controller = {
        index: function () {
            ea.listen();
        },
    };
    return Controller;
});