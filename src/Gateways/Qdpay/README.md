## 使用介绍

本接口为591支付系统接口

```php
配置
'QdPay' => [
        'merchantNo' => '',
        'pay_urls' => [
            weixin_scan => 微信扫码
            alipay_scan => 支付宝扫码
            qq_scan     => qq扫码
            jd_scan     => 京东支付扫码
            union_scan  => 银联扫码
        ],
        'order_query_url' => '',
        'private_key' => ''
    ]
```


#### 1、实例化类
```php
string $payStyle 支付接口，例如：$payStyle = 'qdpay' 代表采用该通道支付;

$bosincePay = new BosincePay($payStyle);
```

#### 2、网银支付接口请求参数说明
```php
bank => 网银支付

$param = [
    "user_id"      => 105
    'service_type' => 'bank' // 支付类型
    'via'          => '' // 入金方式
    'bank_code'    => 'CCB' // 网银银行渠道编码
    'product_name' => 'test', // 商品名称
    'amount'       => 10,  // 美元
    'order_amount' => 10, // 实际交易金额 （人民币 以分为单位）
    'return_url'   => '前台跳转链接'
];


$bankPay = $bosincePay->bankPay($param)->toArray(); // 此处返回数组;toObject 返回对象; toJson 返回json字符串
```

#### 2.1、网银接口返回参数说明
```php
"pay" =>[
    "merchantNo" => "shanghubianhao" // 商户编号
    "orderNo" => "111111" // 订单号
    "merType" => "01" // // 连接方式  01直连 02转接
    "tranChannel" => "CCB" // 网银银行渠道编码
    "txnAmt" => 0.1  // 实际交易金额(元)
    "merUrl" => "houtaiyibu"  // 服务器异步通知地址
    "pageUrl" => "qiantai"  // 前台跳转地址
    "merData" => 1  // 扩展信息 1.借记卡 2.贷记卡
    "sign" => "01cd1fe0a8a9048765d6f2ba6809f17e" // 签名
  ]
```

### 3、扫码支付
```php
weixin_scan => 微信扫码
alipay_scan => 支付宝扫码
qq_scan     => qq扫码
jd_scan     => 京东支付扫码
union_scan  => 银联扫码
$param = [
    "user_id"      => 105
    'pay_type'     => 'weixin_scan' // 支付类型
    'product_name'   => 'test', // 商品名称
    'amount'       => 10,  // 美元
    'order_amount' => 10, // 实际交易金额 （人民币 以分为单位）
];
$scanPay = $bosincePay->scanPay($param)->toArray();

```

#### 3.1、扫码支付接口应答参数说明
```php
数据形式以json格式返回
"pay" =>['交易二维码']
```

#### 4、处理支付接口后台异步通知传递的数据(进行部分数据的存储)
```php
// @param  array   $post 支付方同步返回的数据（$post = $_POST）
$offlineNotify = $bosincePay->offlineNotify($post);

// @return int
// 1：验证失败，说明数据来源异常，返回——0
// 2：验证成功，且支付成功，第一次通知，返回——deposit_id
// 3：验证成功，且支付成功，非第一次通知，返回——SUCCESS
// 4：验证成功，支付失败，返回——原始数据的trade_status
```


#### 5、订单查询
```php
order_no => 需要查询的订单号
$data = [
    'order_no' => "111111"
]
$orderQuery = $bosincePay->orderQuery($data)->toArray();
```

#### 5.1、订单查询返回参数说明
```php
数据形式以json格式返回
array => [
    "r1_MerchantNo" => "shanghubianhao" // 商户编号
    "r2_OrderNo" => "111111" // 订单号
    "r3_Amount" => "01" // 支付金额
    "r4_ProductName" => "test" // 商品名称
    "r5_TrxNo" => "2222222"  // 平台支付流水号
    "ra_Status" => "100"  // 订单状态 100:成功，101:失败，102:未支付，103:已取消，104：系统异常
    "rb_Code" => "10001"  // 响应码
    "rc_CodeMsg" => "验证签名失败"  // 响应码描述
    "sign" => "01cd1fe0a8a9048765d6f2ba6809f17e" // 签名
  ]
```
