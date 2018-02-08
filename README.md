# Laravel Payment

简称『LaraPay』是为 Laravel 量身定制的 第三方支付 扩展包，可根据第三方支付平台提供的 API 接口，无限扩展本包支持的平台。特别适合国产三四方支付平台。

LaraPay 维护一个数据库表 payments，单纯记录支付历史和状态。实际使用中，需要其它与应用相关的数据，可另设数据表，并通过中间表与 payments 关联的形式进行维护。

扩展支付平台，只需采用 PSR-4 规则，并将命名空间设置如 `Sungmee\LaraPay\Gateways\Example`，并继承基础类 Base `Sungmee\LaraPay\Base` 和接口 `Sungmee\LaraPay\GatewayInterface` 即可，具体请参考 Example 示例。

## 扩展包安装使用

### 安装

命令行到 Laravel 项目根目录然后：

> composer require sungmee/laravel-api-response

然后进行数据库迁移：

> php artisan migrate

最后在 .env 中加入实际支付验签数据。

```php
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
```

更多请参考 config/payment.php

### 支付与查询

```php
use Sungmee\LaraPay\Facade as Pay;

// 支付参数
$params = [];

// 银联支付，返回 POST 表单数组，用于前台组装跳转到支付页面
Pay::bankPay($params);

// 联合支付，返回 POST 表单数组，用于前台组装跳转到支付页面
Pay::unionPay($params);

// 扫码支付，返回二维码图片链接或二维码数据字符串
Pay::scanPay($params);

// 订单状态查询，返回 \Sungmee\LaraPay\Payment $payment 与查询结果数组组合
$order_no = 888; // 订单号，等同于 payments ID
Pay::query($order_no);
```

### 支付结果异同步通知

已使用路由 routers/payment.php 和 通知控制器 NotifyController.php，如需改写业务逻辑，请参考相应代码。

异同步通知后，订单验签成功并且状态改变为已支付『CAPTURE』时，会产生一个已支付事件 `Sungmee\LaraPay\Events\Capture`，事件带有 `\Sungmee\LaraPay\Payment $payment` 实例变量 $this->payment。可进行下一步操作，如其它相关业务逻辑流程。

### 运行中指定支付平台

```php
use Sungmee\LaraPay\Pay;

$pay = new Pay;
$gateway = $pay->Example();

$gateway->bankPay();
$gateway->unionPay();
$gateway->scanPay();
...
```