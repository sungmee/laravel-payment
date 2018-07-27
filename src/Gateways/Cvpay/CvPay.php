<?php

namespace Sungmee\LaraPay\Gateways\CvPay;

use Sungmee\LaraPay\Base;
use Sungmee\LaraPay\GatewayInterface;

/**
 * cv支付系统
 * =============================================================
 *
 * 支付存库状态
 * PENDING - 待处理。
 * VOID - 无效，当订单在付款前被取消的状态。
 * REFUNDED - 已退款。当订单在付款后被取消或退回时的状态。
 * CAPTURE - 已付款。
 * SUCCESS - 支付流程已完成。
 * FAIL - 支付失败。
 *
 * ============================================================
 */
class CvPay extends Base implements GatewayInterface
{
    protected $merchantNo;
    protected $notify_url;
    protected $return_url;
    protected $pay_url;
    protected $bankCode;
    protected $Md5key;

    /**
     * CvPay constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $conf = $this->config['gateways']['CvPay'];
        $this->notify_url  = $this->config['notifyUrl'];
        $this->return_url  = $this->config['returnUrl'];

        $this->merchantNo  = $conf['pay_memberid'];
        $this->bankCode    = $conf['pay_bankcode'];
        $this->pay_url    = $conf['pay_url'];
        $this->Md5key      = $conf['Md5key'];
        // 支付返回数据
        $this->metaKeys = [
            'returncode', // cv支付返回 支付状态 00 为成功
            'transaction_id', // 流水号
        ];
    }

    /**
     *
     * @author Yuki
     * @param array $params
     * @return array
     */
    public function unionPayAlias($params)
    {
        $data = [
            'status' => 'Error',
            'answer' => 'No Payment Methods'
        ];
        return $data;
    }

    /**
     * 快捷支付
     * @author Yuki
     * @param array $params 接口接收到的数据
     * @return array 返回组装好的数据
     */
    public function bankPayAlias($params)
    {
        $param_data = [
            'pay_memberid'  => $this->merchantNo,
            'pay_orderid'   => $params[$this->paymentNo],
            'pay_amount'    => round($params['amount'] / 100,2),
            'pay_applydate' => date("Y-m-d H:i:s"),  //订单时间
            'pay_bankcode'  => $this->bankCode,
            "pay_notifyurl" => $this->notify_url,
            "pay_callbackurl" => $this->return_url,
        ];

        $param_data['pay_md5sign'] = $this->getCardSign($param_data);
        $param_data['pay_bankname'] = $params['bank_code'];//银行编码

        return $param_data;
    }

    /**
     * 扫码支付
     * @author Yuki
     * @param array $params
     * @return array
     */
    public function scanPayAlias($params)
    {
        $data = [
            'status' => 'Error',
            'answer' => 'No Payment Methods'
        ];
        return $data;
    }

    /**
     * 支付结果同步通知
     * @author Yuki
     * @param $payment
     * @param  array $request
     * @return mixed
     */
    public function pageNotifyAlias($payment, $request)
    {
        $data['first'] = false;
        $data['validate'] = true;
        // 检测签名
        $checkSign = $this->checkSign($request);
        if (!$checkSign) {
            $data = [
                'validate' => false,
                'answer'   => 'Verification Error'
            ];
            return $data;
        }

        // 如果支付成功
        if ($request['returncode'] == '00') {
            // 支付成功且第一次通知
            $order = $this->find($request['orderid'])->toArray();
            if ($order && $order['status'] != 'SUCCESS') {
                $data['first'] = true;
            }
            $data['status'] = 'CAPTURE'; // 订单状态 已付款
            $data['answer'] = 'OK';
        } else {
            $data = [
                'status' => 'FAIL',
                'answer' => 'Payment FAIL'
            ];
        }
        // 返回metas数据
        $data['metas'] = $this->getGatewayMetas($request);
        return $data;
    }

    /**
     * 支付结果异步通知
     * @author Yuki
     * @param $payment
     * @param  array $request
     * @return mixed
     */
    public function offlineNotifyAlias($payment, $request)
    {
        $data['first'] = false;
        $data['validate'] = true;
        // 检测签名
        $checkSign = $this->checkSign($request);
        if (!$checkSign) {
            $data = [
                'validate' => false,
                'answer'   => 'Verification Error'
            ];
            return $data;
        }

        // 如果支付成功
        if ($request['returncode'] == '00') {
            // 支付成功且第一次通知
            $order = $this->find($request['orderid'])->toArray();
            if ($order && $order['status'] != 'SUCCESS') {
                $data['first'] = true;
            }
            $data['status'] = 'CAPTURE'; // 订单状态 已付款
            $data['answer'] = 'OK';
        } else {
            $data = [
                'status' => 'FAIL',
                'answer' => 'Payment FAIL'
            ];
        }
        // 返回metas数据
        $data['metas'] = $this->getGatewayMetas($request);
        return $data;
    }

    /**
     * 查询订单
     * @author Yuki
     * @param  $params
     * @return array
     */
    public function queryAlias($params)
    {
        $data = [
            'status' => 'Error',
            'answer' => 'No Payment Methods'
        ];
        return $data;
    }

    /**
     * 构造签名 按照文档给定的参数顺序拼接参数生成签名
     * @author Yuki
     * @param array $params
     * @return array|string
     */
    public function getCardSign(array $params)
    {
        ksort($params);
        reset($params);
        $md5str = "";
        foreach ($params as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $md5str .= "key=" . $this->Md5key;
        return strtoupper(md5($md5str));
    }

    /**
     * 验证签名
     * @author Yuki
     * @param array $return_data 返回的数据
     * @return bool
     */
    public function checkSign($return_data)
    {
        $returnArray = array( // 返回字段
            "memberid"       => $return_data["memberid"], // 商户ID
            "orderid"        => $return_data["orderid"], // 订单号
            "amount"         => $return_data["amount"], // 交易金额
            "datetime"       => $return_data["datetime"], // 交易时间
            "transaction_id" => $return_data["transaction_id"], // 支付流水号
            "returncode"     => $return_data["returncode"],
        );
        $sign = $this->getCardSign($returnArray);
        if ($sign == $return_data['sign']) {
            return true;
        }
        return false;
    }
}