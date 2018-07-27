<?php

$appUrl = env('APP_URL');

return [
	// 默认支付平台
	'gateway' => env('PAY_GATEWAY', 'Example'),

	// 支付平台商户数据
	'gateways' => [
		'Example' => [],
        'CvPay' => [
            'pay_memberid' => "10002", // 商户号
            'pay_url' 	   => 'https://www.paycv.com/Pay_Index.html',  // 支付地址
            'pay_bankcode' => "907", //通道编码
            'Md5key' 	   => 't4ig5acnpx4fet4zapshjacjd9o4bhbi', // 加密秘钥
        ],
        'DinPay' => [
            'merchant_code'     => '',
            'interface_version' => 'V3.3',
            'scanpay_url' 	    => 'https://api.ddbill.com/gateway/api/scanpay',
            'bank_url' 			=> 'https://pay.ddbill.com/gateway?input_charset=UTF-8',
            'query_url' 		=> 'https://query.ddbill.com/query',
            'private_key' 		=> '',
            'public_key' 		=> '',
            'dinpay_public_key' => '',
        ],
        'WbPay' => [
            'version' 	  => 'V1.0', // 版本号
            'merNo' 	  => '8800345000032', // 商户号
            'pay_url'	  => 'http://df.wanbipay.com/api/index',  // 支付地址
            'notify_url'  => 'http://laravel.com', // 异步通知地址
            'private_key' => '/test/8800345000032_m_prv.pem', // 私钥
            'public_key'  => '/test/8800345000032_mp_pub.pem' // 公钥
        ],
        'QdPay' => [
            'merchantNo' => '',
            'pay_urls'   => [
                'weixin_scan' => '微信扫码',
                'alipay_scan' => '支付宝扫码',
                'qq_scan'     => 'qq扫码',
                'jd_scan'     => '京东支付扫码',
                'union_scan'  => '银联扫码'
            ],
        'order_query_url' => '',
        'private_key'     => ''
        ]
	],

	// 用户模型的命名空间
	'userModel' => env('PAY_USER_MODEL', 'App\User'),

	// 同步、异步通知地址
	'returnUrl' => env('PAY_RETURN_URL', "$appUrl/payment/notify/page"),
	'notifyUrl' => env('PAY_NOTIFY_URL', "$appUrl/payment/notify/offline"),

	// 支付成功同步通知后跳转到的网址
	'redirectTo' => env('PAY_REDIRECT_TO', $appUrl),

	// 基础配置
	'fee'    => env('PAY_FEE', 0.01), // 每笔手续费的百分比
	'erDiff' => env('PAY_ER_DIFF', 0.07), // 汇率差值，即实时汇率减去该值

	// 银行代码
	'bankCodes' => [
		'ICBC' 		=> '工商银行',
		'ABC' 		=> '农业银行',
		'CCB' 		=> '建设银行',
		'BCOM' 		=> '交通银行',
		'BOC' 		=> '中国银行',
		'PSBC' 		=> '邮政银行',
		'CMB' 		=> '招商银行',
		'CMBC' 		=> '民生银行',
		'CEBB' 		=> '光大银行',
		'BOB' 		=> '北京银行',
		'BOS' 		=> '上海银行',
		// 'SHB' 		=> '上海银行',
		'NBB' 		=> '宁波银行',
		'HXB' 		=> '华夏银行',
		'CIB' 		=> '兴业银行',
		'SPABANK'	=> '平安银行',
		'SPDB' 		=> '浦发银行',
		'ECITIC'	=> '中信银行',
		'HZB' 		=> '杭州银行',
		'GDB' 		=> '广发银行',
		'NJCB' 		=> '南京银行'
	],
];