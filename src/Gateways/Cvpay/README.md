# 使用介绍

本接口为cv支付系统接口

```php
配置
'CvPay' => [
        'pay_memberid' => "10002", // 商户号
        'pay_url' => 'https://www.paycv.com/Pay_Index.html',  // 支付地址
        'pay_bankcode' => "907", //通道编码
        'Md5key' => 't4ig5acnpx4fet4zapshjacjd9o4bhbi', // 加密秘钥
    ]
```

## 1、实例化类

```php
string $payStyle 支付接口，例如：$payStyle = 'cvpay' 代表采用该通道支付;

$bosincePay = new BosincePay($payStyle);
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


$bankPay = $bosincePay->bankPay($param)->toArray(); // 此处返回数组;toObject 返回对象; toJson 返回json字符串
```

## 2.1、网银接口返回参数说明

```php
"pay" =>[
    "pay_memberid" => "shanghubianhao" // 商户编号
    "pay_orderid" => "111111" // 订单号
    "pay_amount" => 10 // 金额 单位为分
    "pay_applydate" => date("Y-m-d H:i:s"),  //订单时间
    "pay_bankcode" => 907  //通道编码
    "pay_notifyurl" => "houtaiyibu"  // 服务器异步通知地址
    "pay_callbackurl" => "qiantai"  // 前台跳转地址
    "pay_md5sign" => "" // 签名
    "pay_bankname" => 'CCB' // 网银银行渠道编码
  ]
```

## 3、处理支付接口后台异步通知传递的数据(进行部分数据的存储)

```php
// @param  array   $post 支付方同步返回的数据（$post = $_POST）
$offlineNotify = $bosincePay->offlineNotify($post);

"return" =>[
    "memberid" => $_REQUEST["memberid"], // 商户ID
    "orderid" => $_REQUEST["orderid"], // 订单号
    "amount" => $_REQUEST["amount"], // 交易金额
    "datetime" => $_REQUEST["datetime"], // 交易时间
    "transaction_id" => $_REQUEST["transaction_id"], // 支付流水号
    "returncode" => $_REQUEST["returncode"],  // 交易状态  “00” 为成功
  ]

// @return int
// 1：验证失败，说明数据来源异常，返回——0
// 2：验证成功，且支付成功，第一次通知，返回——deposit_id
// 3：验证成功，且支付成功，非第一次通知，返回——OK
// 4：验证成功，支付失败，返回——原始数据的trade_status

如果接收到服务器点对点通讯时，在页面输出“OK”（没有双引号，OK两个字母大写）,否则会重复5次发送点对点通知
```
