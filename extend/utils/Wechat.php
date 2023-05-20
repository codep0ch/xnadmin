<?php
namespace utils;
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
class Wechat
{
    public $instance = null;
    public function createWechatPay($merchantId, $merchantPrivateKeyFile, $merchantCertificateSerial, $platformCertificateFilePath): Wechat
    {
        try {
            // 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名(这里的文件采用微信支付API安全中生成的证书key)
            $merchantPrivateKeyFile = 'file://'.$merchantPrivateKeyFile;
            $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFile, Rsa::KEY_TYPE_PRIVATE);

            // 「商户API证书」的「证书序列号」 (微信支付API安全中获取的证书序列号)
            $merchantCertificateSerial = empty($merchantCertificateSerial) ?? 0;

            // 从本地文件中加载「微信支付平台证书」，用来验证微信支付应答的签名 (这里的文件采用使用CertificateDownloader生成的证书)
            $platformCertificateFilePath = 'file://'.$platformCertificateFilePath;
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
        }catch (\Exception $e){
            $this->instance = new \stdClass();
        }
        return $this;
    }

    /**
     * 该方法用于验证是否成功
     * @return void
     */
    public function sign_test(){
        // 发送请求
        try {
            $resp = $this->instance->chain('v3/certificates')->get();
            $array = json_decode($resp->getBody(), true);
            return !empty($array['data'][0]['serial_no']);
        } catch (\Exception $e){
            return false;
        }

    }
}
