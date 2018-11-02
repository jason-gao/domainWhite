<?php

/**
 * HyHlDomainWhiteSdk::setConfig(xx);
 * HyHlDomainWhiteSdk::addDomainWhite(xx, xx);
 * HyHlDomainWhiteSdk::delDomainWhite(xx, xx);
 */

namespace DomainWhiteSdk;


use DomainWhiteSdk\Http\RawRequest;
use DomainWhiteSdk\HttpClients\GuzzleHttpClient;
use DomainWhiteSdk\Logger\MonologLogger;

class HyHlDomainWhiteSdk
{
    private static $config;
    private static $client;

    public static $body;
    public static $encBody;

    public function __construct()
    {
    }

    public static function setConfig($config)
    {
        self::$config = $config;
    }

    public static function getNormalConfig()
    {
        return self::$config['normal'];
    }


    public static function getLogger()
    {
        $logger = null;
        if (self::getNormalConfig()['sdk_log_switch']) {
            $logger = MonologLogger::getLoggerInstance(__CLASS__, self::getNormalConfig()['sdk_log']);
        }

        return $logger;
    }

    public static function getClient()
    {
        if (!self::$client) {
            self::$client = new GuzzleHttpClient(null, self::getLogger());
        }

        return self::$client;
    }

    public static function getPublicKey()
    {
        $publicKey = self::getNormalConfig()['public_key'];

        return $publicKey;
    }

    public static function getApiKey()
    {
        $apiKey = self::getNormalConfig()['api_key'];

        return $apiKey;
    }


    public static function getRequestHeaders()
    {
        $header = array(
            'Content-Type' => 'application/x-www-form-urlencoded'
        );

        return $header;
    }

    public static function apiCall($url, $method, $body, $headers = [], $timeOut = 20, $options = [])
    {
        $url               = self::getNormalConfig()['base_api_url'] . $url;
        $body['user']      = self::getNormalConfig()['user'];
        $body['userpass']  = md5(self::getNormalConfig()['userpass']);
        $body['timestamp'] = time();
        if ($body && is_array($body)) {
            foreach ($body as $k => &$v) {
                if ($k != 'timestamp') {
                    $v = self::encrypts($v, self::getApiKey());
                }
            }
        }
        self::$body = $body;
        if (self::getLogger()) {
            self::getLogger()->info("body encrypt before:" . print_r($body, 1));
        }
        $encBody['encrypt'] = self::code(json_encode($body));
        self::$encBody      = $encBody;
        $encBody            = RawRequest::build_query($encBody);
        if (strtolower($method) == 'get') {
            $url .= $encBody;
        }
        $rawResponse = self::getClient()->send($url, $method, $encBody, $headers, $timeOut, $options);
        $resBody     = $rawResponse->getBody();

        return $resBody;
    }

    /**
     * @param string $url
     * @param array $data
     * @return string
     * @node_name
     * @link
     * @desc
     *
     * $data = [
     *      "domain" => 'xx',
     *      "ip" => "xx",
     *      "authority" => "备案号",
     *      "timestamp" => "时间戳"
     * ]
     */
    public static function addDomainWhite($url = 'index.php?s=/HyApi/addDomain', $data = [])
    {
        $headers = self::getRequestHeaders();
        $res     = self::apiCall($url, 'post', $data, $headers);

        return $res;
    }


    /**
     * @param string $url
     * @param array $data
     * @return string
     * @node_name
     * @link
     * @desc
     *
     * $data = [
     *      "domain" => 'xx',
     *      "timestamp" => "时间戳"
     * ]
     */
    public static function delDomainWhite($url = 'index.php?s=/HyApi/delDomain', $data = [])
    {
        $headers = self::getRequestHeaders();
        $res     = self::apiCall($url, 'post', $data, $headers);

        return $res;
    }


    public static function code($data)
    {
        $encrypted = '';
        if ($data != "") {
            $public_key = self::getPublicKey();
            if (self::getLogger()) {
                self::getLogger()->info('public key:' . $public_key);
            }
            $pu_key = openssl_pkey_get_public($public_key); //这个函数可用来判断公钥是否是可用的
            if (!$pu_key) {
                if (self::getLogger()) {
                    self::getLogger()->error("public key invalid");
                }
            }
            $encrypted = "";
            $encrypt   = openssl_public_encrypt($data, $encrypted, $public_key); //公钥加密
            if (!$encrypt) {
                if (self::getLogger()) {
                    self::getLogger()->error("encrypt fail");
                }
            }
            $encrypted = base64_encode($encrypted);
            if ($encrypted) {
                if (self::getLogger()) {
                    self::getLogger()->info('encrypted:' . $encrypted);
                }
            }

            return $encrypted;
        }

        return $encrypted;
    }

    public static function encrypts($data, $key = "@#$%@#$$#@#$#")
    {
        $char = '';
        $str  = '';
        $key  = md5($key);
        $x    = 0;
        $len  = strlen($data);
        $l    = strlen($key);
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= $key{$x};
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
        }
        return base64_encode($str);
    }

    public static function decrypts($data, $key = "@#$%@#$$#@#$#")
    {
        $char = '';
        $str  = '';
        $key  = md5($key);
        $x    = 0;
        $data = base64_decode($data);
        $len  = strlen($data);
        $l    = strlen($key);
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return $str;
    }

}
