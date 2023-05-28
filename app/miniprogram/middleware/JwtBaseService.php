<?php
namespace app\miniprogram\middleware;

use Exception;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;

/**
 * 单例 一次请求中所有出现jwt的地方都是一个用户
 * Class JwtAuth
 * @package app\api\middleware
 */
class JwtBaseService
{
    private $config;
    private $key = "PoY2TRndVttHXZQ21r0gEvEEbPvL9KN";
    private $iss = "www.codepoch.com";//颁发者(iss声明)
    private $aud = "www.codepoch.com";//访问群体(aud声明)
    private $jti = "codepoch"; //id（jti声明）
    private $iat = "codepoch"; //id（jti声明）
    private $expTime = 2;//令牌有效时间,单位小时
    private static $instance;// 单例模式JwtAuth句柄

    // 获取JwtAuth的句柄
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 构造初始化配置
     * JwtToken constructor.
     */
    public function __construct()
    {
        self::init();
    }

    public function setKey(string $key)
    {
        $this->key = $key;
        self::init();
    }

    private function init()
    {
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::base64Encoded($this->key)
        );
        $this->config = $config;
    }

    /**
     * 创建JWT
     * @param array $arrClaim
     * @return string
     * @throws Exception
     * @author 一颗大萝北 mail@bugquit.com
     */
    public function createToken(array $arrClaim)
    {
        $config = $this->config;
        assert($config instanceof Configuration);
        if (count($arrClaim) == count($arrClaim, 1)) {
            throw new Exception("claim参数必须为二维数组");
        }
        $now = new \DateTimeImmutable();

        $token = $config->builder()
            // 配置颁发者（iss声明）
            ->issuedBy($this->iss)
            // 配置访问群体（aud声明）
            ->permittedFor($this->aud)
            // 配置id（jti声明）
            ->identifiedBy($this->jti)
            // 配置令牌发出的时间（iat声明）
            ->issuedAt($now)
            // 配置令牌的过期时间（exp claim）
            ->expiresAt($now->modify("+{$this->expTime} hour"));
        //写入claim
        foreach ($arrClaim as $k => $item) {
            $token = $token->withClaim($k, $item);
        }
        // 生成新令牌
        $token = $token->getToken($config->signer(), $config->signingKey());
        return $token->toString();
    }

    /**
     * 解析token
     * @param string $jwt
     * @return mixed
     * @author 一颗大萝北 mail@bugquit.com
     */
    public function parseToken(string $jwt)
    {
        $config = $this->config;
        $token = $config->parser()->parse($jwt);
        return $token->claims();
    }


    /**
     * 验证令牌
     * @param $jwt
     * @return mixed
     * @throws \think\Exception
     * @throws Exception
     * @author 一颗大萝北 mail@bugquit.com
     */
    public function validatorToken($jwt)
    {
        $config = $this->config;
        $token = $config->parser()->parse($jwt);
        $claims = $token->claims();
        $jti = (string)$claims->get('jti');
        $iss = (string)$claims->get('iss');
        $aud = $claims->get('aud');
        $exp = $claims->get('exp');
        $now = new \DateTimeImmutable();
        // 是否过期
        if ($exp < $now) {
            throw new Exception("身份已过期");
        }
        //验证jwt id是否匹配
        $validate_jwt_id = new \Lcobucci\JWT\Validation\Constraint\IdentifiedBy($jti);
        // 验证签发人url是否正确
        $validate_issued = new \Lcobucci\JWT\Validation\Constraint\IssuedBy($iss);
        // 验证客户端url是否匹配
        $validate_aud = new \Lcobucci\JWT\Validation\Constraint\PermittedFor($aud[0]);
        $config->setValidationConstraints($validate_jwt_id, $validate_issued, $validate_aud);
        $constraints = $config->validationConstraints();
        //验证方法2
        if (!$config->validator()->validate($token, ...$constraints)) {
            throw new Exception("非法的请求");
        }
        return $claims;
    }
}