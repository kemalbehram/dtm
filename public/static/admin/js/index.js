define(["jquery", "easy-admin", "echarts", "echarts-theme", "miniAdmin", "miniTab"], function ($, ea, echarts, undefined, miniAdmin, miniTab) {

    var Controller = {
        index: function () {
            var options = {
                iniUrl: ea.url('ajax/initAdmin'),    // 初始化接口
                clearUrl: ea.url("ajax/clearCache"), // 缓存清理接口
                urlHashLocation: true,      // 是否打开hash定位
                bgColorDefault: 0,      // 主题默认配置
                multiModule: true,          // 是否开启多模块
                menuChildOpen: false,       // 是否默认展开菜单
                loadingTime: 0,             // 初始化加载时间
                pageAnim: false,             // iframe窗口动画
                maxTabNum: 20,              // 最大的tab打开数量
            };
            miniAdmin.render(options);

            $('.login-out').on("click", function () {
                ea.request.get({
                    url: 'login/out',
                    prefix: true,
                }, function (res) {
                    ea.msg.success(res.msg, function () {
                        window.location = ea.url('login/index');
                    })
                });
            });
        },
        welcome: function () {
            miniTab.listen();
        },
        editPassword: function () {
            ea.listen();
        }
    };
    return Controller;
});