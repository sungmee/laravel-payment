<?php

namespace Sungmee\LaraPay\Platforms\Example;

use Sungmee\LaraPay\Base;
use Ixudra\Curl\Facades\Curl;	// 外部通讯使用该扩展包，除非必须，勿需重新定义
use ungmee\LaraPay\PlatformInterface;

class Example extends Base implements PlatformInterface
{
	/**
	 * 改写传递给第三方支付平台的自定义订单号的键名。
	 *
	 * @var string
	 */
	protected $paymentNo = 'ex';

	/**
	 * 初始化父类构造函数
	 *
	 * 父类 \Sungmee\LaraPay\Base 中定义了 config 变量
	 * 本支付平台配置获取如下所示：
	 * $platform = $this->config['platform'];	// 默认支付平台
	 * $platforms = $this->config['platforms'];	// 各支付平台配置
	 * $example_config = $platforms[$platform];	// 本实例支付配置
	 *
	 * @var void
	 */
    public function __construct()
    {
		parent::__construct();
	}
}