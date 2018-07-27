<?php
/**
 * @author Yuki
 * @Date 2017/12/11
 * @Time 10:32
 *
 * 万币聚合支付
 * ========================================================
 * 支付金额单位为分 12字节定长的整型数值
 * bankPay 网银支付
 * scanPay 扫码支付
 * offlineNotify 支付结果后台异步通知
 * orderQuery 订单查询
 *
 * ========================================================
 */

namespace Sungmee\LaraPay\Gateways\WbPay;

use Sungmee\LaraPay\Base;
use Sungmee\LaraPay\GatewayInterface;
use Ixudra\Curl\Facades\Curl;

class WbPay extends Base implements GatewayInterface
{
    protected $merNo;
    protected $notify_url;
    protected $return_url;
    protected $pay_url;
    protected $version;
    protected $public_key;
    protected $private_key;
    /**
     * 创建一个新的任务实例。
     */
    public function __construct()
    {
        parent::__construct();
        $conf = $this->config['gateways']['WbPay'];
        $this->notify_url  = $this->config['notifyUrl'];
        $this->return_url  = $this->config['returnUrl'];

        $this->merNo       = $conf['merNo'];
        $this->pay_url     = $conf['pay_url'];
        $this->version     = $conf['version'];
        $this->private_key = $conf['private_key'];
        $this->public_key  = $conf['public_key'];

        // 支付返回数据
        $this->metaKeys = [
            'respCode', // wb支付返回
            'respDesc',
            'orderDate',
            'origRespCode',
            'origRespDesc',
            'payId',
            'payTime',
        ];
    }

    /**
     * 联合支付
     * @author Yuki
     * @param array $params 接口接收到的数据
     * @return array 返回组装好的数据
     */
    public function unionPayAlias($params)
    {
        // 支付金额在0~99中随机加个数值
        $params['amount'] = (int)$params['amount'] + rand(0, 99);
        $order = $this->find($params[$this->paymentNo])->toArray();
        $request_no = $params['user_id'] . '_' . strtotime($order['created_at']);

        $params_post = array(
            'requestNo'     => $request_no,	// 交易请求流水号 由用户id与订单生成时间戳拼接
            'orderNo'       => $params[$this->paymentNo],	// 商户订单号
            'returnUrl'     => $this->return_url, // 页面通知地址
            'notifyUrl'     => $this->notify_url, // 异步通知地址
            'transAmt'      => (int)$params['amount'], // 交易金额 单位为分
            'commodityName' => $params['product_name'], // 商品名称
        );
        $params_post = $this->getParams($params_post, 'bank');
        // 签名
        $params_post["signature"] = $this->getSignature($params_post);

        return $params_post;
    }

    /**
     * 网银支付
     * @author Yuki
     * @param array $params 接口接收到的数据
     * @return array 返回组装好的数据
     */
    public function bankPayAlias($params)
    {
        $data = [
            'status' => 'Error',
            'answer' => 'No Payment Methods'
        ];
        return $data;
    }

    /**
     * 扫码支付
     * @author Yuki
     * @param array $params 接口接收到的数据
     * @return mixed 返回远程请求的数据
     */
    public function scanPayAlias($params)
    {
        // 支付金额在0~99中随机加个数值
        $params['amount'] = (int)$params['amount'] + rand(0, 99);
        $order = $this->find($params[$this->paymentNo])->toArray();
        $request_no = $params['user_id'] . '_' . strtotime($order['created_at']);
        // 构造需要传输的数据格式
        $params_post = array(
            'requestNo'     => $request_no,	//交易请求流水号
            'orderNo'       => $params[$this->paymentNo],	// 商户订单号
            'returnUrl'     => $this->return_url, // 页面通知地址
            'notifyUrl'     => $this->notify_url, // 异步通知地址
            'transAmt'      => (int)$params['amount'], // 交易金额 单位为分
            'commodityName' => $params['product_name'], // 商品名称
        );
        $params_post = $this->getParams($params_post, 'scan');
        // 签名
        $params_post["signature"] = $this->getSignature($params_post);
        // 向后台传送数据
        $response = Curl::to($this->pay_url)->withData($params_post)->post();
        $response = json_decode($response, true);
        $result = [];
        // 返回支付二维码
        if (isset($response['codeUrl'])) {
            $result['qrcode'] = $response['codeUrl'];
        }

        return $result;
    }

    /**
     * 支付结果后台同步通知
     * @author Yuki
     * @param $payment
     * @param  array $request  post返回的数据是数组形式
     * @return mixed
     */
    public function pageNotifyAlias($payment, $request)
    {
        $data['first'] = false;
        $data['validate'] = true;

        $return_signature = $request['signature'];
        // 验证签名
        $check_signature = $this->checksignature($request, $return_signature);

        if (!$check_signature) {
            $data = [
                'validate' => false,
                'answer'   => 'Verification Error'
            ];
            return $data;
        }

        switch ($request['respCode']) {
            case '0000':
                // 支付成功且第一次通知
                $order = $this->find($request['orderNo'])->toArray();
                if ($order && $order['status'] != 'SUCCESS') {
                    $data['first'] = true;
                }
                $data['status'] = 'CAPTURE'; // 订单状态 已付款
                $data['answer'] = 'success';
                break;
            case 'P000':
                $data['status'] = 'PENDING'; // 订单状态 已付款
                $data['answer'] = 'PENDING';
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
     * 支付结果后台异步通知
     * @author Yuki
     * @param $payment
     * @param  array $request  post返回的数据是数组形式
     * @return mixed
     */
    public function offlineNotifyAlias($payment, $request)
    {
        $data['first'] = false;
        $data['validate'] = true;

        $return_signature = $request['signature'];
        // 验证签名
        $check_signature = $this->checksignature($request, $return_signature);

        if (!$check_signature) {
            $data = [
                'validate' => false,
                'answer'   => 'Verification Error'
            ];
            return $data;
        }

        switch ($request['respCode']) {
            case '0000':
                // 支付成功且第一次通知
                $order = $this->find($request['orderNo'])->toArray();
                if ($order && $order['status'] != 'SUCCESS') {
                    $data['first'] = true;
                }
                $data['status'] = 'CAPTURE'; // 订单状态 已付款
                $data['answer'] = 'success';
                break;
            case 'P000':
                $data['status'] = 'PENDING'; // 订单状态 已付款
                $data['answer'] = 'PENDING';
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
        $data['status'] = 'FAIL';
        if (!isset($request['order_no'])) {
            $data['response'] = 'OrderID Error';
            return $data;
        }
        $order = $this->find($request['order_no'])->toArray();
        if (!$order) {
            $data['response'] = 'Order not longer exist';
            return $data;
        }
        $order_date = date('Ymd', strtotime($order['created_at']));
        $request_no = $order->user_id . '_' . strtotime($order['created_at']);
        // 构造需要传输的数据格式
        $param_data = [
            'transId'      => '05', // 交易类型
            'requestNo'    => $request_no, // 流水号
            'orderNo'      => $request['order_no'],  // 订单号
            'orderPayType' => '01', // 订单类型 01消费、02代付
            'orderDate'    => $order_date, // 订单时间
        ];
        $param_data = $this->getParams($param_data);
        // 签名
        $param_data["signature"] = $this->getSignature($param_data);
        // 查询订单状态
        $response = Curl::to($this->pay_url)->withData($param_data)->post();
        $response = json_decode($response, true);
        $return_signature = $response['signature'];
        $check_signature = $this->checksignature($response, $return_signature);
        // 验证签名是否正确
        if (!$check_signature) {
            $data = [
                'status' => 'FAIL', // 订单状态
                'response'   => 'Verification Error'
            ];
            return $data;
        }

        switch ($request['respCode']) {
            case '0000':
                $data['status'] = 'CAPTURE';
                break;
            case 'P000':
                $data['status'] = 'PENDING';
                break;

            default:
                $data['status'] = 'FAIL';
                break;
        }
        $data['response'] = $response;

        return $data;
    }

    /**
     * 组合必需参数
     * @author Yuki
     * @param $params
     * @param $type
     * @return array
     */
    public function getParams(array $params, $type = null)
    {
        $needs = [
            'version' => $this->version,	// 版本号
            'merNo'   => $this->merNo, // 商户号
        ];
        switch ($type) {
            case 'bank':
                $needs['productId'] = '8001';    // 产品类型
                $needs['transId']   = '12';  // 交易类型
                $needs['orderDate'] = date('Ymd');
                //$needs['bankPayType'] = '01';  // 网银类型 01:B2C网银
                //$needs['cardType']  = '1';   // 银行卡类型 1：借记卡 2：贷记卡
                break;
            case 'scan':
                $needs['productId'] = '1001';    // 产品类型
                $needs['transId']   = '10';  // 交易类型
                $needs['orderDate'] = date('Ymd');
                $needs['subMerNo'] = '0000022';  // 支付商户识别id 该项以及以下项 应写在配置文件
                $needs['subMerName']  = '测试商户';  // 支付收款商户名称
                break;

            default:
                # code...
                break;
        }
        $params = array_merge($needs, $params);
        return $params;
    }

    /**
     * 生成签名
     * @author Yuki
     * @param $params_signature
     * @return string
     */
    public function getSignature(array $params_signature){
        $params_signature = array_filter($params_signature);
        ksort($params_signature); //自然排序
        $sign_string = "";
        $i = 0;
        foreach ($params_signature as $key => $val){
            $sign_string = $sign_string.($i==0 ? $key."=".$val : ('&'.$key."=".$val));
            $i++;
        }

        // 调用证书生成签名
        $wb_rsa = new WbpayRsa($this->public_key, $this->private_key);
        $signature = $wb_rsa->sign(sha1($sign_string));

        return $signature;
    }

    /**
     * 验证签名
     * @author Yuki
     * @param $return_params
     * @param $return_signature
     * @return bool
     */
    public function checkSignature($return_params, $return_signature){
        unset($return_params['signature']);
        $return_params = array_filter($return_params); // 过滤空元素
        ksort($return_params);//自然排序
        $sign_string = "";
        $i = 0;
        foreach ($return_params as $key => $val){
            $sign_string = $sign_string.($i==0 ? $key."=".$val : ('&'.$key."=".$val));
            $i++;
        }

        // 调用证书验证签名
        $wb_rsa = new WbpayRsa($this->public_key, $this->private_key);
        $result = $wb_rsa->verify(sha1($sign_string), $return_signature);

        return $result;
    }

}