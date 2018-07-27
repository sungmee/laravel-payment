# 使用介绍

本接口为多得宝支付系统接口

```php
配置
'DinPay' => [
        'merchant_code' => '',
        'interface_version' => 'V3.3',
        'scan_url' => 'https://api.ddbill.com/gateway/api/scanpay',
        'bank_url' => 'https://pay.ddbill.com/gateway?input_charset=UTF-8',
        'query_url' => 'https://query.ddbill.com/query',
        'private_key' => '',
        'public_key' => '',
        'dinpay_public_key' => '',
    ]
```

## 1、实例化类

```php

$bosincePay = new DinPay();
```

## 2、网银支付接口请求参数说明

```php
bank => 网银支付

$param = [
    "user_id"      => 105
    'service_type' => 'direct_pay' // 支付类型
    'via'          => 'CRD' // 入金方式
    'bank_code'    => 'CCB' // 网银银行渠道编码
    'product_name' => 'test', // 商品名称
    'amount'       => 10,  // 美元
    'order_amount' => 10, // 实际交易金额 （人民币 以分为单位）
];


$bankPay = $bosincePay->bankPay($param);
```

## 2.1、网银接口返回参数说明

```php
array:13 [▼
  "interface_version" => "V3.3"
  "merchant_code" => "1111110166"
  "sign_type" => "RSA-S"
  "input_charset" => "UTF-8"
  "order_time" => "2018-02-27 16:42:18"
  "notify_url" => "http://laravel.com"
  "return_url" => "http://laravel.com"
  "order_no" => 1519720938
  "service_type" => "direct_pay"
  "order_amount" => 100.0
  "bank_code" => "ABC"
  "product_name" => "name"
  "sign" => ""
```

