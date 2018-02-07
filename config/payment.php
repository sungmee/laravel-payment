<?php

$appUrl = env('APP_URL');

return [
	// 默认支付平台
	'gateway' => env('PAY_GATEWAY', 'example'),

	// 支付平台商户数据
	'gateways' => [
		'example' => []
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