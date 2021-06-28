<?php

/**
 * 腾讯云短信验证
 */

return [
    // 短信应用SDK AppID
    'appid'     => 1400009099,

    // 短信应用SDK AppKey
    'appkey'    => "",

    // 签名内容
    'sms_sign'  => "腾讯云",

    // 发送间隔限制 s
    'interval'  =>  60,

    //验证码有效期
    'expire'    =>  1800,
];
