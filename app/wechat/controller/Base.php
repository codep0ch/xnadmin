<?php
namespace app\wechat\controller;
use app\common\model\WechatSetting as WechatSettingModel;
use EasyWeChat\Factory;
use GuzzleHttp\Exception\GuzzleException;
use Overtrue\Socialite\Exceptions\AuthorizeFailedException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class Base extends \app\common\controller\Base
{
    public $wechatSetting = [];
    public $wechatApp = null;

    /**
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws GuzzleException
     * @throws AuthorizeFailedException
     */
    public function __construct(App $app){
        $wechat_setting_data = WechatSettingModel::find(1);
        $this->wechatSetting = [
            'app_id' => $wechat_setting_data['wechatAppId'],
            'secret' => $wechat_setting_data['wechatAppSecret'],
            'merchantId' => $wechat_setting_data['merchantId'],
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
            //...
        ];

        $this->wechatApp = Factory::officialAccount($this->wechatSetting);
        // 未登录
        if (empty(session('wechat_user'))) {
            if(empty(app()->request->get('code'))){
                // $redirectUrl 为跳转目标，请自行 302 跳转到目标地址
                $redirectUrl = $this->wechatApp->oauth->scopes(['snsapi_userinfo'])
                    ->redirect(app()->request->url(true));
                $this->redirect($redirectUrl);
            }else{
                $user = $this->wechatApp->oauth->userFromCode(app()->request->get('code'));
                session('wechat_user', $user->toArray());
            }
        }
    }

    /**
     * 微信 API V2 签名
     * @param $params
     * @param $privateKey
     * @param string $type sha256:HMAC-SHA256签名方式 me5:MD5签名方式
     * @return string
     */
    function getSignV2($params, $privateKey, $type = "sha256")
    {
        #参数名ASCII码从小到大排序（字典序）
        ksort($params);
        #初始化数据
        $stringToBeSigned = "";
        $i = 0;
        #循环拼接请求参数
        foreach ($params as $k => $v) {
            if (false === $this->checkWxEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->wxCharaCet($v, "UTF-8");
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . urlencode($v);
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . urlencode($v);
                }
                $i++;
            }
        }
        #清除缓存
        unset ($k, $v);
        #拼接密钥
        $stringSignTemp = $stringToBeSigned . "&key=" . $privateKey;
        #签名方式 sha256、md5
        if ($type == "sha256") {
            $sign = strtoupper(hash_hmac("sha256", $stringSignTemp, $privateKey));
        } else {
            $sign = strtoupper(md5($stringSignTemp));
        }
        #返回
        return $sign;
    }

    /**
     * 转换成目标字符集
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function wxCharaCet($data, $targetCharset): string
    {
        if (!empty($data)) {
            $fileType = "UTF-8";
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }
        return $data;
    }


    /**
     * 判断参数值是否为空
     * @param $value
     * @return bool
     */
    function checkWxEmpty($value): bool
    {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }
}