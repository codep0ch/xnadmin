<?php
namespace utils;
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
class Wechat
{
    public $instance = null;
    public function createWechatPay(): Wechat
    {
        // 商户号
        $merchantId = '1644958700';

        // 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
        $merchantPrivateKeyFile = 'file://'.app()->getRootPath().'/extend/utils/cert/apiclient_key.pem';
        $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFile, Rsa::KEY_TYPE_PRIVATE);

        // 「商户API证书」的「证书序列号」
        $merchantCertificateSerial = '5244495AA3C4C59788075C7892C116C1BAF2A61C';

        // 从本地文件中加载「微信支付平台证书」，用来验证微信支付应答的签名
        $platformCertificateFilePath = 'file://'.app()->getRootPath().'/extend/utils/cert/apiclient_cert.pem';
        $platformPublicKeyInstance = Rsa::from($platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);

        // 从「微信支付平台证书」中获取「证书序列号」
        $platformCertificateSerial = PemUtil::parseCertificateSerialNo($platformCertificateFilePath);

        // 构造一个 APIv3 客户端实例
        $this->instance = Builder::factory([
            'mchid'      => $merchantId,
            'serial'     => $merchantCertificateSerial,
            'privateKey' => $merchantPrivateKeyInstance,
            'certs'      => [
                $platformCertificateSerial => $platformPublicKeyInstance,
            ],
        ]);
        return $this;
    }
    public function test(){
        // 发送请求
        $resp = $this->instance->chain('v3/certificates')->get(
            ['debug' => true] // 调试模式，https://docs.guzzlephp.org/en/stable/request-options.html#debug
        );
        echo $resp->getBody();
    }
}
