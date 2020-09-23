<?php


namespace FormItem\TengxunCos;


use Think\Exception;

class TengxunCos
{
    static public function getInstance(){
        return new self();
    }

    private function __construct(){

    }

    public function getAuthorization($pathname,$method='GET',$queryParams = array(),$headers = array()){

        $secretId = env("COS_SECRETID"); //"云 API 密钥 SecretId";
        $secretKey = env("COS_SECRETKEY"); //"云 API 密钥 SecretKey";

        // 获取个人 API 密钥 https://console.qcloud.com/capi
        $sid = $secretId;
        $skey = $secretKey;

        // 工具方法
        function getObjectKeys($obj)
        {
            $list = array_keys($obj);
            sort($list);
            return $list;
        }

        function obj2str($obj)
        {
            $list = [];
            $keyList = getObjectKeys($obj);
            $len = count($keyList);
            for ($i = 0; $i < $len; $i++) {
                $key = $keyList[$i];
                $val = isset($obj[$key]) ? $obj[$key] : '';
                $key = strtolower($key);
                $key = urlencode($key);
                $list[] = $key . '=' . urlencode($val);
            }
            return implode('&', $list);
        }

        // 签名有效起止时间
        $now = time() - 1;
        $expired = $now + 600; // 签名过期时刻，600 秒后

        // 要用到的 Authorization 参数列表
        $qSignAlgorithm = 'sha1';
        $qAk = $sid;
        $qSignTime = $now . ';' . $expired;
        $qKeyTime = $now . ';' . $expired;
        $qHeaderList = strtolower(implode(';', getObjectKeys($headers)));
        $qUrlParamList = strtolower(implode(';', getObjectKeys($queryParams)));

        // 签名算法说明文档：https://www.qcloud.com/document/product/436/7778
        // 步骤一：计算 SignKey
        $signKey = hash_hmac("sha1", $qKeyTime, $skey);

        // 步骤二：构成 FormatString
        $formatString = implode("\n", array(strtolower($method), $pathname, obj2str($queryParams), obj2str($headers), ''));

        // 步骤三：计算 StringToSign
        $stringToSign = implode("\n", array('sha1', $qSignTime, sha1($formatString), ''));

        // 步骤四：计算 Signature
        $qSignature = hash_hmac('sha1', $stringToSign, $signKey);

        // 步骤五：构造 Authorization
        $authorization = implode('&',array(
            'q-sign-algorithm=' . $qSignAlgorithm,
            'q-ak=' . $qAk,
            'q-sign-time=' . $qSignTime,
            'q-key-time=' . $qKeyTime,
            'q-header-list=' . $qHeaderList,
            'q-url-param-list=' . $qUrlParamList,
            'q-signature=' . $qSignature
        ));

        return $authorization;

    }

    private function _handleCosUrl($url){
        $res=[];
        $parse=parse_url($url);
        $res['protocol']=$parse['scheme'];
        $res['key']=$parse['path'];

        $host=explode('.',$parse['host']);
        $res['region']=$host[2];
        $res['bucket']=$host[0];

        return $res;
    }

    /**
     * 获取对象元数据
     * @param $bucket_host
     * @param $key
     * @return array
     */
    public function headObj($bucket_host,$key){
        $handle=$this->_handleCosUrl($bucket_host);

        $secretId = env('COS_SECRETID'); //替换为您的永久密钥 SecretId
        $secretKey = env('COS_SECRETKEY'); //替换为您的永久密钥 SecretKey
        $region = $handle['region']; //设置一个默认的存储桶地域
        $cosClient = new \Qcloud\Cos\Client(
            array(
                'region' => $region,
                'schema' => $handle['protocol'], //协议头部，默认为 http
                'credentials' => array(
                    'secretId' => $secretId,
                    'secretKey' => $secretKey)));
        /** @var \GuzzleHttp\Command\Result $obj */
        $obj = $cosClient->headObject([
            'Bucket' => $handle['bucket'],
            'Key' => $key
        ]);
        return $obj->toArray();
    }

    /**
     * @param int $file_id filePic的id
     * @param int $expire 过期时间(秒)
     * @return mixed|string
     */
    public function getFileUrl($file_id,$expire=600){
        $file=D('FilePic')->find($file_id);
        if ($file['security']){
            return $this->_genSignUrl($file['url'],$expire);
        }
        return $file['url'];
    }

    private function _genSignUrl($cos_url, $expire){
        $handle=$this->_handleCosUrl($cos_url);

        $secretId = env('COS_SECRETID'); //替换为您的永久密钥 SecretId
        $secretKey = env('COS_SECRETKEY'); //替换为您的永久密钥 SecretKey
        $region = $handle['region']; //设置一个默认的存储桶地域
        $cosClient = new \Qcloud\Cos\Client(
            array(
                'region' => $region,
                'schema' => $handle['protocol'], //协议头部，默认为 http
                'credentials'=> array(
                    'secretId'  => $secretId,
                    'secretKey' => $secretKey)));

### 使用封装的 getObjectUrl 获取下载签名
        try {
            $bucket =  $handle['bucket']; //存储桶，格式：BucketName-APPID
            $key = $handle['key']; //对象在存储桶中的位置，即对象键
            $signedUrl = $cosClient->getObjectUrl($bucket, $key, '+'.$expire.' seconds'); //签名的有效时间
            // 请求成功
            return $signedUrl;
        } catch (\Exception $e) {
            // 请求失败
            E($e);
        }
    }
}