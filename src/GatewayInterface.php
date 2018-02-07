<?php
/**
 * 支付存库状态
 * PENDING - 待处理。
 * VOID - 无效，当订单在付款前被取消的状态。
 * REFUNDED - 已退款。当订单在付款后被取消或退回时的状态。
 * CAPTURE - 已付款。
 * SUCCESS - 支付流程已完成。
 * FAIL - 支付失败。
 */
namespace Sungmee\LaraPay;

interface GatewayInterface
{
    /**
     * 第三方平台异步通知。
     *
     * @param  \Sungmee\LaraPay\Payment $payment
     * @param  \Illuminate\Http\Request $request
     * @return array
	 *
	 * 返回示例
	 * [
	 * 		'validate'	=> 'bool'							// 验签成功返回 true，失败 false。失败时下面各元素除 answer 外，可不返回
	 * 		'first'		=> 'bool'							// 支付成功（status==CAPTURE）并且是第一次，返回 true，其它均 false
	 * 		'status'	=> 'select:CAPTURE|FAIL...',		// 固定值，根据第三方状态改写成存库状态。请参考上方支付存库状态说明
	 * 		'answer'	=> 'string:SUCCESS'					// 根据第三方平台返回应答字符串
	 * 		'metas'		=> 'array'							// 第三方返回的需要存库的参数，键值对数组
	 * ]
     */
	public function offlineNotifyAlias($payment, $request);

    /**
     * 第三方平台页面跳转（同步）通知。
     *
     * @param  \Sungmee\LaraPay\Payment $payment
     * @param  \Illuminate\Http\Request $request
     * @return array
	 *
	 * 返回示例，参考 『第三方平台异步通知』
     */
	public function pageNotifyAlias($payment, $request);

    /**
     * 第三方平台银行/联合支付方法。
     *
     * @param  array	$params		支付明细
     * @return array				返回给前端做 POST 跳转的所有必须数据数组
	 *
	 * $params 示例
	 * [
	 * 		'no'		=> int		\Sungmee\LaraPay\Payment ID
	 * 		'amount'	=> int		支付金额
	 * 		'name'		=> string	商品名称
	 * 		'type'		=> string	支付服务，如 direct_pay, union_pay ...
	 * 		'bank'		=> string	银行代码
	 * 		...
	 * ]
	 *
	 * 返回示例，用于前端组合 POST 表单
	 * [
	 * 		'sign' 		=> string,
	 * 		'amount'	=> int,
	 * 		...
	 * ]
     */
	public function bankPayAlias($params);
	public function unionPayAlias($params);

    /**
     * 第三方平台扫码支付方法。
     *
     * @param  array	$params		支付明细
     * @return array				返回给前端做 Post 跳转的所有必须数据数组
	 *
	 * $params 示例
	 * [
	 * 		'no'		=> int		\Sungmee\LaraPay\Payment ID
	 * 		'amount'	=> int		支付金额
	 * 		'name'		=> string	商品名称
	 * 		'type'		=> string	支付服务，如 weixin_scan, alipay_scan, qq_scan, ylpay_scan ...
	 * 		...
	 * ]
	 *
	 * 返回示例
	 * [
	 * 		'qrcode' => string 二维码链接/内容字符串
	 * 		...
	 * ]
     */
	public function scanPayAlias($params);

    /**
     * 第三方平台订单查询。
     *
     * @param  \Sungmee\LaraPay\Payment $payment
     * @return array					第三方返回的订单明细数组
	 *
	 * 返回示例
	 * [
	 * 		'status' 	=> select:PENDING|VOID|REFUNDED|CAPTURE|SUCCESS|FAIL	根据第三方返回数据定义状态
	 * 		'response'	=> array												第三方返回的订单明细数组
	 * ]
     */
	public function queryAlias($payment);
}