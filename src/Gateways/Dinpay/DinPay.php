<?php
/**
 * @Date 2018/2/27
 *
 * 多得宝支付
 */

namespace Sungmee\LaraPay\Gateways\DinPay;

use Sungmee\LaraPay\Base;
use Sungmee\LaraPay\GatewayInterface;
use Ixudra\Curl\Facades\Curl;


class DinPay extends Base implements GatewayInterface
{
    protected $merNo;
    protected $notify_url;
    protected $return_url;
    protected $scan_url;
    protected $query_url;
    protected $version;
    protected $public_key;
    protected $private_key;
    /**
     * 多得宝公钥
     */
    protected $dinpay_public_key;

    /**
     * 创建一个新的任务实例。
     */
    public function __construct()
    {
        parent::__construct();
        $conf = $this->config['gateways']['DinPay'];
        $this->notify_url  = $this->config['notifyUrl'];
        $this->return_url  = $this->config['returnUrl'];

        $this->merNo       = $conf['merchant_code'];
        $this->scan_url    = $conf['scan_url'];
        $this->query_url   = $conf['query_url'];
        $this->version     = $conf['interface_version'];
        $this->private_key = $conf['private_key'];
        $this->public_key  = $conf['public_key'];
        $this->dinpay_public_key = $conf['dinpay_public_key'];

        // 支付返回数据
        $this->metaKeys = [
            'trade_no', // 多得宝支付返回
            'trade_time',
            'trade_status',
            'bank_seq_no',
        ];
    }

    /**
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
     *
     * @param array $params
     * @return array
     */
    public function bankPayAlias($params)
    {
        $param_data = [
            'order_no'     => $params[$this->paymentNo],	// 商户订单号
            'service_type' => $params['service_type'],
            'order_amount' => round($params['amount'] / 100,2),
            'bank_code'    => $params['bank_code'],
            'product_name' => $params['product_name'],
        ];
        $param_data = $this->getParams($param_data);
        $param_data['sign'] = $this->getCardSign($param_data);

        return $param_data;
    }

    /**
     * @param array $params
     * @return array
     */
    public function scanPayAlias($params)
    {
        $param_data = [
            'order_no'     => $params[$this->paymentNo],	// 商户订单号
            'service_type' => $params['service_type'],
            'order_amount' => round($params['amount'] / 100,2),
            'client_ip'    => $params['client_ip'],
            'product_name' => $params['product_name'],
        ];
        $param_data = $this->getParams($param_data);
        $param_data['sign'] = $this->getCardSign($param_data);

        // 向后台传送数据
        $response = $this->xmlToArray(Curl::to($this->scan_url)->withData($param_data)->post());
        $response = $response['response'];
        $result = [];
        // 返回支付二维码
        if (isset($response['qrcode'])) {
            $result['qrcode'] = $response['qrcode'];
        }

        return $result;
    }

    /**
     * @param $payment
     * @param  array $request  post返回的数据是数组形式
     * @return mixed
     */
    public function pageNotifyAlias($payment, $request)
    {
        $data['first'] = false;
        $data['validate'] = true;

        $return_sign = base64_decode($request['sign']);
        // 验证签名
        $check_signature = $this->checkSign($request, $return_sign);

        if (!$check_signature) {
            $data = [
                'validate' => false,
                'answer'   => 'Verification Error'
            ];
            return $data;
        }

        switch ($request['trade_status']) {
            case 'SUCCESS':
                // 支付成功且第一次通知
                $order = $this->find($request['order_no'])->toArray();
                if ($order && $order['status'] != 'SUCCESS') {
                    $data['first'] = true;
                }
                $data['status'] = 'CAPTURE'; // 订单状态 已付款
                $data['answer'] = 'SUCCESS';
                break;
            default:
                $data = [
                    'status' => 'FAIL',
                    'answer' => 'Payment FAIL'
                ];
                break;
        }

        // 返回metas数据
        $data['metas'] = $this->getGatewayMetas($request);
        return $data;
    }

    /**
     * @param $payment
     * @param  array $request  post返回的数据是数组形式
     * @return mixed
     */
    public function offlineNotifyAlias($payment, $request)
    {
        $data['first'] = false;
        $data['validate'] = true;

        $return_sign = base64_decode($request['sign']);
        // 验证签名
        $check_signature = $this->checkSign($request, $return_sign);

        if (!$check_signature) {
            $data = [
                'validate' => false,
                'answer'   => 'Verification Error'
            ];
            return $data;
        }

        switch ($request['trade_status']) {
            case 'SUCCESS':
                // 支付成功且第一次通知
                $order = $this->find($request['order_no'])->toArray();
                if ($order && $order['status'] != 'SUCCESS') {
                    $data['first'] = true;
                }
                $data['status'] = 'CAPTURE'; // 订单状态 已付款
                $data['answer'] = 'SUCCESS';
                break;
            default:
                $data = [
                    'status' => 'FAIL',
                    'answer' => 'Payment FAIL'
                ];
                break;
        }

        // 返回metas数据
        $data['metas'] = $this->getGatewayMetas($request);
        return $data;
    }

    /**
     * 支付状态查询
     * @author Yuki
     * @param array $request
     * @return mixed
     */
    public function queryAlias($request)
    {
        if (!isset($request['order_no'])) {
            $data = [
                'status' => 'FAIL', // 订单状态
                'response' => 'OrderID Error'
            ];

            return $data;
        }

        // 构造需要传输的数据格式
        $param_data = [
            'service_type'      => 'single_trade_query', // 交易类型
            'interface_version' => $this->version,	// 版本号
            'merchant_code'     => $this->merNo, // 商户号
            'sign_type'         => 'RSA-S',
            'order_no'          => $request['order_no'],  // 订单号
        ];

        // 签名
        $param_data["sign"] = $this->getCardSign($param_data);
        // 查询订单状态
        $response = $this->xmlToArray(Curl::to($this->query_url)->withData($param_data)->post());
        $response = $response['response'];
        $data = [];
        if (isset($response['is_success']) && $response['is_success'] == 'T') {
            $return_sign = base64_decode($response['sign']);
            $check_signature = $this->checkSign($response['trade'], $return_sign);
            // 验证签名是否正确
            if (!$check_signature) {
                $data = [
                    'status' => 'FAIL', // 订单状态
                    'response'   => 'Verification Error'
                ];
                return $data;
            }

            switch ($response['trade']['trade_status']) {
                case 'SUCCESS':
                    $data['status'] = 'CAPTURE';
                    break;
                case 'UNPAY':
                    $data['status'] = 'PENDING';
                    break;

                default:
                    $data['status'] = 'FAIL';
                    break;
            }
            $data['response'] = $response;
        }
        return $data;
    }

    /**
     * 组合必需参数
     * @param $params
     * @return array
     */
    public function getParams(array $params)
    {
        $needs = [
            'interface_version' => $this->version,	// 版本号
            'merchant_code'     => $this->merNo, // 商户号
            'sign_type'         => 'RSA-S',
            'input_charset'     => 'UTF-8',
            'order_time'        => date( 'Y-m-d H:i:s' ),
            'notify_url'        => $this->notify_url,
            'return_url'        => $this->return_url,
        ];

        $params = array_merge($needs, $params);
        return $params;
    }

    /**
     * 获取sign值
     * （除了sign_type参数，其他非空参数都要参与组装，组装顺序是按照a~z的顺序，下划线"_"优先于字母）
     *
     * @date   2017-07-29 15:37
     * @param  array $sign_param 签名数组
     * @return string
     */
    public function getCardSign(array $sign_param)
    {
        // 一．形成签名字符串
        if (isset($sign_param['sign_type'])) {
            unset($sign_param['sign_type']); // 签名方式，不生字符串
        }
        ksort($sign_param);

        $sign_str = '';
        // 形成组装字符串
        foreach ($sign_param as $key => $value) {
            $value = trim($value);
            if ($value != '') {
                // 0也是要参与组装的
                $sign_str .= $key . '=' . $value . '&';
            }
        }
        // 去掉最右边的＆符号
        $sign_str = rtrim($sign_str, '&');

        // 二．获取sign值
        $merchant_private_key = openssl_get_privatekey($this->private_key);
        openssl_sign($sign_str, $sign_info, $merchant_private_key, OPENSSL_ALGO_MD5);
        $sign = base64_encode($sign_info);

        return $sign;
    }

    /**
     * 验证签名
     * @author Yuki
     * @param $return_params
     * @param $return_sign
     * @return bool
     */
    public function checkSign($return_params, $return_sign){
        // 不参与签名的字段
        unset($return_params['sign_type']);
        unset($return_params['sign']);

        // 对参数进行排序
        ksort($return_params);

        // 二．签名字符串
        $sign_str = '';
        foreach ($return_params as $key => $value) {
            if ($value != '') {
                $sign_str .= $key . '=' . $value . '&';
            }
        }
        $sign_str = rtrim($sign_str, '&');
        $dinpay_public_key = openssl_get_publickey($this->dinpay_public_key);
        $flag = openssl_verify($sign_str, $return_sign, $dinpay_public_key, OPENSSL_ALGO_MD5);

        return $flag ? true : false;
    }

}