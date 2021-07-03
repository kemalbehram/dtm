$(function () {
    $('.languages').on('click', '.toggle', function () {
        $('.languages').toggleClass('open');
    });

    $('.js-model').on('click', function () {
        // let address = getAddress();
        // if (empty(address)) {
        //     layer.msg('请先连接钱包', {icon:2, skin:'white'}, function () {});
        //     return;
        // }

        var id = $(this).data('id');
        $('#' + id).fadeIn();
        $('html').css('overflow', 'hidden');
    });

    $('.model-container').on('click', '.close, .overlay', function () {
        $(this).parents('.model-container').fadeOut();
        $('html').css('overflow', 'visible');
    });
});

function getAddress() {
    if(window.tronWeb && window.tronWeb.defaultAddress.base58){
        return window.tronWeb.defaultAddress.base58;
    }
    return;
}

function getUserInfo() {
    let address = getAddress();
    if (empty(address)) {
        layer.msg('请先连接钱包', {icon:2, skin:'white'}, function () {});
        return;
    }
    $.ajax({
        url: '/ajax/getUserInfo',
        dataType: 'json',
        data: {address: address},
        success: function (res) {
            if (res.code == 1){
                try {
                    //渲染可选数据
                    $('.amount1').html(res.data.amount1);
                    $('.amount2').html(res.data.amount2);
                    $('.amount3').html(res.data.amount3);
                    $('.static_award').html(res.data.static_award);
                    $('.direct_award').html(res.data.direct_award);
                    $('.manage_award').html(res.data.manage_award);
                    $('.all_award').html(res.data.all_award);
                    $('.quota').html(res.data.quota);
                    $('.share_num').html(res.data.share_num);
                    $('.team_num').html(res.data.team_num);
                    $('.team_order_amount').html('A线：' + res.data.a_performance + '，B线：' + res.data.b_performance);
                    $('.invite_url').val(res.data.invite_url);
                }catch (e) {}
            }
        },
        error: function () {
            console.log('error');
        }
    });
}

function get_order() {
    let address = getAddress();
    if (empty(address)) {
        layer.msg('请先连接钱包', {icon:2, skin:'white'}, function () {});
        return;
    }
    $.ajax({
        url: '/ajax/getOrder',
        dataType: 'json',
        data: {address: address},
        success: function (res) {
            if (res.code == 1){
                //渲染数据
                $('.order-list').html(
                    layui.laytpl(
                        $('#list').html()
                    ).render(res.data)
                );
            }
        },
        error: function () {
            console.log('error');
        }
    });
}

$(window).scroll(function () {
    if ($(document).scrollTop() > 50) {  //距离顶部大于100px时
        $('.header').addClass('header-fix')
    } else {
        $('.header').removeClass('header-fix')
    }
});

function start() {
    let type = $('#type').val();
    let amount = $('#amount').val();
    let address = getAddress();
    if (empty(address)) {
        layer.msg('请先连接钱包', {icon:2, skin:'white'}, function () {});
        return;
    }
    if (empty(amount)) {
        layer.msg('请输入投资数量', {icon:2, skin:'white'}, function () {});
        return;
    }
    if (empty(type)) {
        layer.msg('请选择付款账户', {icon:2, skin:'white'}, function () {});
        return;
    }
    $.ajax({
        url: '/start',
        type: 'POST',
        dataType: 'json',
        data: {type: type, amount: amount, address: address},
        success: function (res) {
            console.log(res);
            if (res.code == 1){
                layer.msg(res.msg, {icon: 1});
                window.location.reload();
                return true;
            }
            layer.msg(res.msg, {icon: 2});
        },
        error: function () {
            console.log('error');
        }
    });
}

function get_money_log() {
    let address = getAddress();
    if (empty(address)) {
        return;
    }
    $.ajax({
        url: '/ajax/getMoneyLog',
        type: 'POST',
        dataType: 'json',
        data: {address: address},
        success: function (res) {
            if (res.code == 1){
                //渲染数据
                $('.order-list').html(
                    layui.laytpl(
                        $('#list').html()
                    ).render(res.data)
                );
            }
        },
        error: function () {
            console.log('error');
        }
    });
}

$('.withdraw').on('click', function () {
    layer.confirm('确定提现全部USDT？', {
        btn: ['确定','取消']
    }, function(){
        let address = getAddress();
        if (empty(address)) {
            layer.msg('请先连接钱包', {icon:2, skin:'white'}, function () {});
            return;
        }
        $.ajax({
            url: '/ajax/withdraw',
            type: 'POST',
            dataType: 'json',
            data: {address: address},
            success: function (res) {
                if (res.code == 1){
                    layer.msg(res.msg, {icon:1});
                    setTimeout(function () {
                       window.location.reload();
                    });
                    return true;
                }
                layer.msg(res.msg, {icon:2});
            },
            error: function () {
                console.log('error');
            }
        });
    }, function(){
    });
});
