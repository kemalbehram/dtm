$(function () {
    $('.languages').on('click', '.toggle', function () {
        $('.languages').toggleClass('open');
    });

    $('.js-model').on('click', function () {
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
    try {
        if (window.ethereum) {
            window.ethereum.enable();
        }
        return window.ethereum.selectedAddress;
    } catch (e) {
        return '';
    }
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
                    //唯一密匙
                    $('#token').val(res.data.token);

                    //渲染可选数据
                    $('.amount1').html(res.data.amount1);
                    $('.amount2').html(res.data.amount2);
                    $('.zy_award').html(res.data.zy_award);
                    $('.tj_award').html(res.data.tj_award);
                    $('.sy_award').html(res.data.sy_award);
                    $('.fh_award').html(res.data.fh_award);
                    $('.all_award').html(res.data.all_award);
                    $('.share_num').html(res.data.share_num);
                    $('.all_recharge').html(res.data.all_recharge);
                    $('.all_withdraw').html(res.data.all_withdraw);
                    $('.invite_url').val(res.data.invite_url);

                    //推广链接是否显示
                    if (res.data.isRecharge60) {
                        $('.invite').show();
                    } else {
                        $('.invite').hide();
                    }

                    $('.buy_fee').html(res.data.buy_fee);
                    $('.auto_buy_bl').html(res.data.auto_buy_bl);
                    $('.dtm_usdt_price').html(res.data.dtm_usdt_price);
                    $('.sell_fee').html(res.data.sell_fee);

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
    let types = $('#types').val();
    let amount = $('#amount').val();
    let address = getAddress();
    let token = $('#token').val();
    if (empty(address)) {
        layer.msg('请先连接钱包', {icon:2, skin:'white'}, function () {});
        return;
    }
    if (empty(types)) {
        layer.msg('请选择质押期限', {icon:2, skin:'white'}, function () {});
        return;
    }
    if (empty(amount)) {
        layer.msg('请输入质押数量', {icon:2, skin:'white'}, function () {});
        return;
    }
    $.ajax({
        url: '/start',
        type: 'POST',
        dataType: 'json',
        data: {types: types, amount: amount, address: address, token:token},
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
    let address = getAddress();
    let token = $('#token').val();

    if (empty(address)) {
        layer.msg('请先连接钱包', {icon:2, skin:'white'}, function () {});
        return;
    }

    layer.prompt({title: '请输入提现数量', formType: 2}, function(num, index){
        layer.close(index);
        $.ajax({
            url: '/ajax/withdraw',
            type: 'POST',
            dataType: 'json',
            data: {address: address, token:token, num:num},
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
    });
});

function buy_amount_calc() {
    var price = parseFloat($('.dtm_usdt_price').html())
        ,buy_fee = parseFloat($('.buy_fee').html())
        ,auto_buy_bl = parseFloat($('.auto_buy_bl').html())
        ,buy_amount = parseFloat($('#buy_amount').val());
    var real_amount = buy_amount / price * (1 - buy_fee / 100) * (1 - auto_buy_bl / 100);
    $('.real_amount').html(real_amount);
}

function sell_amount_calc() {
    var price = parseFloat($('.dtm_usdt_price').html())
        ,sell_fee = parseFloat($('.sell_fee').html())
        ,sell_amount = parseFloat($('#sell_amount').val());
    var sell_real_amount = sell_amount * price * (1 - sell_fee / 100);
    $('.sell_real_amount').html(sell_real_amount);
}

function buy() {
    let amount = parseFloat($('#buy_amount').val());
    let address = getAddress();
    let token = $('#token').val();
    if (empty(address)) {
        layer.msg('请先连接钱包', {icon:2, skin:'white'}, function () {});
        return;
    }
    if (empty(amount)) {
        layer.msg('请输入兑换数量', {icon:2, skin:'white'}, function () {});
        return;
    }
    $.ajax({
        url: '/exchange',
        type: 'POST',
        dataType: 'json',
        data: {type: 1, amount: amount, address: address, token:token},
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

function sell() {
    let amount = parseFloat($('#sell_amount').val());
    let address = getAddress();
    let token = $('#token').val();
    if (empty(address)) {
        layer.msg('请先连接钱包', {icon:2, skin:'white'}, function () {});
        return;
    }
    if (empty(amount)) {
        layer.msg('请输入兑换数量', {icon:2, skin:'white'}, function () {});
        return;
    }
    $.ajax({
        url: '/exchange',
        type: 'POST',
        dataType: 'json',
        data: {type: 2, amount: amount, address: address, token:token},
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
