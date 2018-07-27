<?php

namespace Sungmee\LaraPay\Gateways\QdPay;

use Sungmee\LaraPay\Base;
use Sungmee\LaraPay\GatewayInterface;
use Ixudra\Curl\Facades\Curl;
/**
 * 591支付系统
 * ============================================================
 *
 * bankPay 网银支付
 * scanPay 扫码支付
 * offlineNotify 支付结果后台异步通知
 * orderQuery 订单查询
 *
 * ============================================================
 */
class QdPay extends Base implements GatewayInterface
{
    protected $merchantNo;
    protected $notify_url;
    protected $return_url;
    protected $pay_urls;
    protected $order_query_url;
    protected $private_key;

    /**
     * Qdpay constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $conf = $this->config['gateways']['QdPay'];
        $this->notify_url  = $this->config['notifyUrl'];
        $this->return_url  = $this->config['returnUrl'];

        $this->merchantNo      = $conf['merchantNo'];
        $this->pay_urls        = $conf['pay_urls'];
        $this->order_query_url = $conf['order_query_url'];
        $this->private_key     = $conf['private_key'];

        // 支付返回数据
        $this->metaKeys = [
            'rb_Code',  // qd支付返回
            'rc_CodeMsg',
            'rb_DealTime',
            'ra_PayTime',
            'tranChannel',
            'r5_Status',
            'r5_TrxNo',
            'ra_Status',
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
            'merchantNo'  => $this->merchantNo,
            'orderNo'     => $params[$this->paymentNo],
            'merType'     => '01',  // 连接方式  01直连 02转接
            'tranChannel' => $params['bank_code'], // 银行渠道
            'txnAmt'      => round($params['amount'] / 100,2),
            'merUrl'      => $this->notify_url, // 服务器异步通知地址
            'pageUrl'     => $this->return_url, // 前台跳转地址
            'merData'     => 1,  // 扩展信息 1.借记卡 2.贷记卡
        ];

        $param_data['sign'] = $this->getCardSign($param_data);

        return $param_data;
    }

    /**
     * 扫码支付
     * @author Yuki
     * @param array $params 接口接收到的数据
     * @return mixed 返回远程请求的数据
     */
    public function scanPayAlias($params)
    {
        // 构造需要传输的数据格式
        $param_data = [
            'p1_MerchantNo'  => $this->merchantNo,
            'p2_OrderNo'     => $params[$this->paymentNo],
            'p3_Amount'      => $params['amount'],
            'p4_Cur'         => 1,  // 交易币种 默认设置为1（代表人民币）。
            'p5_ProductName' => $params['product_name'],
            'p6_NotifyUrl'   => $this->notify_url,
        ];
        if ($params['service_type'] == 'alipay_scan') {
            $param_data['tranType'] = 1; // 交易类型 支付方式为支付宝扫码 则该参数必需
        }

        $param_data['sign'] = $this->getCardSign($param_data);

        $param_data['p3_Amount'] = $params['amount'] / 100;  // 如果前台进行了金额放大100倍的处理 则需要除以100
        $pay_urls = $this->pay_urls;
        $url = $pay_urls[$params['pay_type']];
        // 向后台传送数据
        $response = Curl::to($url)->withData($param_data)->post();
        $response = json_decode($response, true);
        // 返回支付二维码
        $result = [];
        if (isset($response['ra_Code'])) {
            $result['qrcode'] = $response['ra_Code'];
        }
        return $result;
    }

    /**
     * 支付结果同步通知
     * @author Yuki
     * @param $payment
     * @param  $request
     * @return mixed
     */
    public function pageNotifyAlias($payment, $request)
    {
        $request = json_decode($request, true);
        // 检测签名
        $data['first'] = false;
        $data['validate'] = true;
        $checkSign = $this->checkSign($request);
        if (!$checkSign) {
            $data = [
                'validate' => false,
                'answer'   => 'Verification Error'
            ];
            return $data;
        }

        // 如果支付成功
        if ($request['r5_Status'] == '100') {
            // 支付成功且第一次通知
            $order = $this->find($request['r2_OrderNo'])->toArray();
            if ($order && $order['status'] != 'SUCCESS') {
                $data['first'] = true;
            }
            $data['status'] = 'CAPTURE'; // 订单状态 已付款
            $data['answer'] = 'success';
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
     * @param  $request
     * @return mixed
     */
    public function offlineNotifyAlias($payment, $request)
    {
        $request = json_decode($request, true);
        // 检测签名
        $data['first'] = false;
        $data['validate'] = true;
        $checkSign = $this->checkSign($request);
        if (!$checkSign) {
            $data = [
                'validate' => false,
                'answer'   => 'Verification Error'
            ];
            return $data;
        }

        // 如果支付成功
        if ($request['r5_Status'] == '100') {
            // 支付成功且第一次通知
            $order = $this->find($request['r2_OrderNo'])->toArray();
            if ($order && $order['status'] != 'SUCCESS') {
                $data['first'] = true;
            }
            $data['status'] = 'CAPTURE'; // 订单状态 已付款
            $data['answer'] = 'success';
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
     * @author Yuki
     * @param array $request
     * @return mixed
     */
    public function queryAlias($request)
    {
        if (!$request['order_no']) {
            $data = [
                'status' => 'FAIL', // 订单状态
                'response' => 'OrderID Error'
            ];

            return $data;
        }

        // 构造需要传输的数据格式
        $param_data = [
            'p1_MerchantNo' => $this->merchantNo,
            'p2_OrderNo'    => $request['order_no'],
        ];
        $param_data['sign'] = $this->getCardSign($param_data);
        $response = Curl::to($this->order_query_url)->withData($param_data)->post();
        $response = json_decode($response, true);
        // 校验签名
        $checkSign = $this->checkSign($response);
        if (!$checkSign) {
            $data = [
                'status' => 'FAIL', // 订单状态
                'response'   => 'Verification Error'
            ];
            return $data;
        }

        switch ($request['ra_Status']) {
            case '100':
                $data['status'] = 'CAPTURE';
                break;
            case '102':
                $data['status'] = 'PENDING';
                break;
            case '103':
                $data['status'] = 'VOID';
                break;
            default:
                $data['status'] = 'FAIL';
                break;
        }
        $data['response'] = $response;
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
        $sign  = array_values($params);
        $sign  = array_filter($sign);
        $sign  = array_map(function ($v) { return trim($v); }, $sign);
        $sign  = join('', $sign);
        $sign .= $this->private_key;
        return md5($sign);
    }

    /**
     * 验证签名
     * @author Yuki
     * @param array $return_data 返回的数据
     * @return bool
     */
    public function checkSign($return_data)
    {
        $sign = $this->getCardSign($return_data);
        if ($sign == $return_data['sign']) {
            return true;
        }
        return false;
    }
}