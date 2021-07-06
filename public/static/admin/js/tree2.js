define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'tree2/index',
    };

    var tree = layui.tree;

    var Controller = {

        index: function () {

            getData();

            function getData() {
                $.ajax({
                    url: ea.url(init.index_url),
                    type: 'POST',
                    dataType: 'json',
                    success: function (res) {
                        tree.render({
                            elem: '#tree'
                            ,data: res,
                        });
                    }
                });
            }

            $('#refresh').on('click', function () {
                getData();
            });

            ea.listen();

        }
    };
    return Controller;
});