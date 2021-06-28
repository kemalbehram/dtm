define(["easy-admin"], function (ea) {

    var Controller = {
        index: function () {
            if (top.location !== self.location) {
                top.location = self.location;
            }

            ea.listen(function (data) {
                data.username = $("input[name='username']").val();
                data.password = $("input[name='password']").val();
                data.captcha  = $("input[name='captcha']").val();
                return data;
            }, function (res) {
                ea.msg.success(res.msg, function () {
                    window.location = ea.url('index');
                })
            }, function (res) {
                ea.msg.error(res.msg, function () {
                    $('#refreshCaptcha').trigger("click");
                });
            });
        },
    };
    return Controller;
});