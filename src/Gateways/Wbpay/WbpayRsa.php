<?php
namespace Sungmee\LaraPay\Gateways\WbPay;
/**
 * @author Yuki
 * @Date 2017/12/11
 * @Time 10:32
 *
 * RSA算法类
 * 签名及密文编码：base64字符串/十六进制字符串/二进制字符串流
 * 填充方式: PKCS1Padding（加解密）/NOPadding（解密）
 *
 * 如密钥长度为1024 bit，则加密时数据需小于128字节，加上PKCS1Padding本身的11字节信息，所以明文需小于117字节
 */
class WbpayRsa
{

    private $pubKey = null;
    private $priKey = null;

    /**
     * 自定义错误处理
     */
    private function _error($msg){
        die('RSA Error:' . $msg); //TODO
    }

    /**
     * 构造函数
     *
     * @param string $public_key 公钥（验签和加密时传入）
     * @param string $private_key 私钥（签名和解密时传入）
     */
    public function __construct($public_key = '', $private_key = ''){
        if ($public_key){
            $this->_getPublicKey($public_key);
        }
        if ($private_key){
            $this->_getPrivateKey($private_key);
        }
    }


    /**
     * 生成签名
     *
     * @param string $data 签名材料
     * @param string $code 签名编码（base64/hex/bin）
     * @return string 签名值
     */
    public function sign($data, $code = 'base64'){
        $ret = false;
        if (openssl_sign($data, $ret, $this->priKey)){
            $ret = $this->_encode($ret, $code);
        }
        return $ret;
    }

    /**
     * 验证签名
     *
     * @param string $data 签名材料
     * @param string $sign 签名值
     * @param string $code 签名编码（base64/hex/bin）
     * @return bool
     */
    public function verify($data, $sign, $code = 'base64'){
        $ret = false;
        $sign = $this->_decode($sign, $code);
        if ($sign !== false) {
            switch (openssl_verify($data, $sign, $this->pubKey)){
                case 1: $ret = true; break;
                case 0:
                case -1:
                default: $ret = false;
            }
        }
        return $ret;
    }

    /**
     * 加密
     *
     * @param string $data 明文
     * @param string $code 密文编码（base64/hex/bin）
     * @param int $padding 填充方式（貌似php有bug，所以目前仅支持OPENSSL_PKCS1_PADDING）
     * @return string 密文
     */
    public function encrypt($data, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING){
        $ret = false;
        if (!$this->_checkPadding($padding, 'en')) $this->_error('padding error');
        if (openssl_public_encrypt($data, $result, $this->pubKey, $padding)){
            $ret = $this->_encode($result, $code);
        }
        return $ret;
    }

    /**
     * 解密
     *
     * @param string 密文
     * @param string 密文编码（base64/hex/bin）
     * @param int 填充方式（OPENSSL_PKCS1_PADDING / OPENSSL_NO_PADDING）
     * @param bool 是否翻转明文（When passing Microsoft CryptoAPI-generated RSA cyphertext, revert the bytes in the block）
     * @return string 明文
     */
    public function decrypt($data, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING, $rev = false){
        $ret = false;
        $data = $this->_decode($data, $code);
        if (!$this->_checkPadding($padding, 'de')) $this->_error('padding error');
        if ($data !== false){
            if (openssl_private_decrypt($data, $result, $this->priKey, $padding)){
                $ret = $rev ? rtrim(strrev($result), "\0") : ''.$result;
            }
        }
        return $ret;
    }


    // 私有方法

    /**
     * 检测填充类型
     * 加密只支持PKCS1_PADDING
     * 解密支持PKCS1_PADDING和NO_PADDING
     *
     * @param int 填充模式
     * @param string 加密en/解密de
     * @return bool
     */
    private function _checkPadding($padding, $type){
        if ($type == 'en'){
            switch ($padding){
                case OPENSSL_PKCS1_PADDING:
                    $ret = true;
                    break;
                default:
                    $ret = false;
            }
        } else {
            switch ($padding){
                case OPENSSL_PKCS1_PADDING:
                case OPENSSL_NO_PADDING:
                    $ret = true;
                    break;
                default:
                    $ret = false;
            }
        }
        return $ret;
    }

    private function _encode($data, $code){
        switch (strtolower($code)){
            case 'base64':
                $data = base64_encode(''.$data);
                break;
            case 'hex':
                $data = bin2hex($data);
                break;
            case 'bin':
            default:
        }
        return $data;
    }

    private function _decode($data, $code){
        switch (strtolower($code)){
            case 'base64':
                $data = base64_decode($data);
                break;
            case 'bin':
            default:
        }
        return $data;
    }

    private function _getPublicKey($key_content){
        if ($key_content){
            $this->pubKey = openssl_get_publickey($key_content);
        }
    }

    private function _getPrivateKey($key_content){
        if ($key_content){
            $this->priKey = openssl_get_privatekey($key_content);
        }
    }

}

?>
