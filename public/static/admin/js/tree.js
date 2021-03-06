define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'tree/index',
    };

    var tree = layui.tree;

    var Controller = {

        index: function () {

            getData();

            function getData() {
                var index = layer.load(0, {shade: false});
                $.ajax({
                    url: ea.url(init.index_url),
                    type: 'POST',
                    dataType: 'json',
                    success: function (res) {
                        layer.close(index);
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