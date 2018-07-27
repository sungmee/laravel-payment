## 使用介绍

本接口为万币支付系统接口 联合支付包含网银，QQ扫码，微信扫码，支付宝扫码，快捷支付

#### 配置参数
```php
'WbPay' => [
    'version' => 'V1.0', // 版本号
    'merNo' => '8800345000032', // 商户号
    'pay_url' => 'http://df.wanbipay.com/api/index',  // 支付地址
    'notify_url' => 'http://laravel.com', // 异步通知地址
    'private_key' => '/test/8800345000032_m_prv.pem', // 私钥  (位于bosincepay\src\Wbpay\rsafile)
    'public_key'  => '/test/8800345000032_mp_pub.pem' // 公钥
]

$conf              = config('pay.wb_config');
$this->notify_url  = config('pay.notify_url');
$this->return_url  = config('pay.return_url');
$this->merNo       = $conf['merNo'];
$this->pay_url     = $conf['pay_url'];
$this->version     = $conf['version'];
$this->private_key = $conf['private_key'];
$this->public_key  = $conf['public_key'];

```


#### 1、实例化类
```php
string $payStyle 支付接口，例如：$payStyle = 'wbpay' 代表采用该通道支付;

$bosincePay = new BosincePay($payStyle);
```

#### 2、联合支付接口请求参数说明
```php
$param = [
    "user_id"      => 105
    'service_type' => 'bank' // 支付类型
    'via'          => '' // 入金方式
    'product_name' => 'test', // 商品名称
    'amount'       => 10,  // 美元
    'order_amount' => 10, // 实际交易金额 （人民币 以分为单位）
    'return_url'   => '前台跳转链接'
];


$bankPay = $bosincePay->unionPay($param)->toArray(); // 此处返回数组;toObject 返回对象; toJson 返回json字符串
```

#### 2.1、联合支付接口返回参数说明
```php
"pay" =>[
    "version" => "V1.0"
    "merNo" => "8800345000032"
    "productId" => "8001"
    "transId" => "12"
    "orderDate" => "20171213"
    "requestNo" => "1_1513149553"
    "orderNo" => "Q201712131519131578"
    "returnUrl" => "http://laravel.com"
    "notifyUrl" => "http://laravel.com"
    "transAmt" => 100
    "commodityName" => "test"
    "signature" => "aUnra5jFdIPzHrL639oK8y2ECz95TTbdzGovGjXWWnAQh/OUYyezEx9mKSWeyq+b6DXbWUlswnSq1MdsQFZfA8vmA7sUUrwvcMs3zZ9kOUVdAOziEPL/+qHcfs8JM4+VpBW0PP1ChvrWH8Q/ymmvUaXMn7bn2NAcSaJCINoE7Aw="
  ]
```

### 3、扫码支付（预留方法，暂时未调）
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
"pay" =>[
    'codeUrl' => 二维码链接,
    'imgUrl'  => 二维码图片
]
```

#### 4、理支付接口后台异步通知传递的数据(进行部分数据的存储)
```php
// @param  array   $post 支付方同步返回的数据（$post = $_POST）
$offlineNotify = $bosincePay->pageNotify($post);

// @return int
// 1：验证失败，说明数据来源异常，返回——0
// 2：验证成功，且支付成功，第一次通知，返回——deposit_id
// 3：验证成功，且支付成功，非第一次通知，返回——SUCCESS
// 4：验证成功，支付失败，返回——原始数据的trade_status
```

#### 5、处理支付接口后台异步通知传递的数据(进行部分数据的存储)
```php
// @param  array   $post 支付方同步返回的数据（$post = $_POST）
$offlineNotify = $bosincePay->offlineNotify($post);

// @return int
// 1：验证失败，说明数据来源异常，返回——0
// 2：验证成功，且支付成功，第一次通知，返回——deposit_id
// 3：验证成功，且支付成功，非第一次通知，返回——SUCCESS
// 4：验证成功，支付失败，返回——原始数据的trade_status
```


#### 6、订单查询
```php
order_no => 需要查询的订单号
$data = [
    'order_no' => "111111"
]
$orderQuery = $bosincePay->orderQuery($data)->toArray();
```

#### 6.1、订单查询返回参数说明
```php
数据形式以json格式返回
array => [
      "version" => "V1.0"
      "merNo" => "8800345000032"
      "transId" => "05"
      "requestNo" => "1_1513135373"  // 流水号
      "orderNo" => "Q201712131122534897"  // 订单号
      "orderPayType" => "01"
      "orderDate" => "20171213"
      "transAmt" => 100   // 支付金额 单位分
      "payId" => "20171213112247657507"  // 万币支付订单号
      "respCode" => "0000"
      "respDesc" => "查询成功"
      "origRespCode" => "P000"   // 原交易应答码
      "origRespDesc" => "交易处理中"  // 原交易应答码描述
      "signature" => "eQ/M5AJ/XzR6Lcju9IOguTtbZ4WWlQBPQ4rIa3GLkcVRvWugk5CkBQ3NyRWJnrO/72Pm73Ro9HCOYZ3I6wHJlKXUk+hhZ45yKBeIR9f/R4rD2Dg+X/52R+onpy+aw4WxAZV5PaKWiwU67fOaithnQ5aPZS6i2Hm0ArPWF9Dmdd8=
  ]
```
