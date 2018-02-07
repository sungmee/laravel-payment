<?php

namespace Sungmee\LaraPay;

abstract class Base
{
	/**
	 * 传递给第三方支付平台的自定义订单号的键名，需根据实际需求重写该变量。
	 *
	 * @var string
	 */
	protected $paymentNo = 'no';

	/**
	 * 支付配置。
	 *
	 * @var array
	 */
	protected $config;

    public function __construct()
    {
		$this->config = config('payment');
	}

	/**
	 * 第三方支付平台支付结果异步通知。
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  string	$method		异步/同步方法分支
	 * @return array	参考接口说明
	 */
	public function notify($request, $method)
	{
		$payment = $this->find($request->{$this->paymentNo});
		$result  = $this->$method($payment, $request);

		if ($result['validate']) {
			$result['payment'] = $this->update($payment, [
				'status' => $result['status'],
				'metas'  => $result['metas']
			]);
		}

		return $result;
	}

	/**
	 * 第三方支付平台支付结果异步通知。
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return array	参考接口说明
	 */
	public function offlineNotify($request)
	{
		return $this->notify($request, 'offlineNotifyAlias');
	}

	/**
	 * 第三方支付平台支付结果页面跳转（同步）通知。
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return array	参考接口说明
	 */
	public function pageNotify($request)
	{
		return $this->notify($request, 'pageNotifyAlias');
	}

	/**
	 * 第三方支付平台支付结果查询。
	 *
	 * @param  int	$id		支付实例 ID
	 * @return array		支付实例与查询结果组合数组
	 */
	public function query($id)
	{
		$payment = $this->find($id);
		$result  = $this->queryAlias($payment);
		return [$payment, $result];
	}

	/**
	 * 第三方支付平台支付总方法。
	 *
	 * @param  array	$params		支付参数数组
	 * @param  string	$method		支付方法分支
	 * @return array	支付实例与验签结果组合数组
	 */
	public function pay($params, $method)
	{
		$payment = $this->store($params);
		$params[$this->paymentNo] = $payment->id;
		$pay = $this->$method($params);

		return [$payment, $pay];
	}

	/**
	 * 第三方支付平台银行支付方法。
	 *
	 * @param  array	$params		支付参数数组
	 * @return array	用于前端的已验签的 POST 表单数据数组
	 */
	public function bankPay($params)
	{
		return $this->pay($params, 'bankPayAlias');
	}

	/**
	 * 第三方支付平台联合支付方法。
	 *
	 * @param  array	$params		支付参数数组
	 * @return array	用于前端的已验签的 POST 表单数据数组
	 */
	public function unionPay($params)
	{
		return $this->pay($params, 'unionPayAlias');
	}

	/**
	 * 第三方支付平台扫码支付方法。
	 *
	 * @param  array	$params		支付参数数组
	 * @return string	二维码数据等数据数组
	 */
	public function scanPay($params)
	{
		return $this->pay($params, 'scanPayAlias');
	}

	/**
	 * 查找支付实例。
	 *
	 * @param  int	$id		支付实例 ID
	 * @return \Sungmee\LaraPay\Payment $payment
	 */
	public function find($id)
	{
		return Payment::find($id);
	}

	/**
	 * 保存支付实例。
	 *
	 * @param  array	$params		支付参数数组
	 * @return \Sungmee\LaraPay\Payment $payment
	 */
	public function store($params)
	{
		$payment = new Payment;
		$payment->user_id = \Auth()->user()->id;
		$payment->gateway = config('payment.gateway');
		$payment->amount  = $params['amount'];

		unset($params['amount']);

		$payment->metas = $params;

		return $payment->save() ? $payment : false;
	}

	/**
	 * 更新支付实例。
	 *
	 * @param  \Sungmee\LaraPay\Payment $payment
	 * @param  array	$params		支付参数数组
	 * @return \Sungmee\LaraPay\Payment $payment
	 */
	public function update(Payment $payment, $data)
	{
		foreach ($data as $column => $value) {
			if ($k == 'metas') {
				$metas = $payment->metas;
				foreach ($value as $k => $v) {
					$metas[$k] = $v;
				}
				$payment->metas = $metas;
			} else $payment->$column = $value;
		}

		return $payment->save() ? $payment : false;
	}

	/**
	 * 删除支付实例。
	 *
	 * @param  int	$id		支付实例 ID
	 * @return bool
	 */
	public function destroy($id)
	{
		return Payment::destroy($id);
	}
}