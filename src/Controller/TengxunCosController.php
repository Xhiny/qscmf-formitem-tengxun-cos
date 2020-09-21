<?php


namespace FormItem\TengxunCos\Controller;


use Qcloud\Cos\Client;
use Think\Controller;

class TengxunCosController extends Controller
{
    public function callBack(){
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: *');
        if (strtolower($_SERVER['REQUEST_METHOD'])==='options'){
            return;
        }

        $config = C('UPLOAD_TYPE_' . strtoupper(I('get.type')));
        if(!$config){
            E('获取不到文件规则config设置');
        }

        $client=new \GuzzleHttp\Client();
        $res=$client->head(rtrim($config['cos_host'],'/').'/'.I('get.key'));
        $header=(array)$res->getHeaders();
        if (!$header){
            header("HTTP/1.1 403 Forbidden");
            echo '上传的文件找不到';
            return;
        }


        if(!empty($config['mimes'])){
            $mimes = explode(',', $config['mimes']);
            if(!in_array(strtolower($header['Content-Type'][0]), $mimes)){
                header("HTTP/1.1 403 Forbidden");
                echo '上传的文件类型不符合要求';
                return;
            }
        }
        if (I('get.title')){
            $file_data['title'] = I('get.title');
        }else {
            $array = explode('/', I('get.key'));
            $file_data['title'] = end($array);
        }

        $file_data['url'] = $config['cos_host'] . '/' . I('get.key') . ($config['oss_style'] ? $config['oss_style'] : '');
        $file_data['size'] = $header['Content-Length'][0];
        $file_data['cate'] = I('get.type');
        $file_data['security'] = $config['security'] ? 1 : 0;
        $file_data['file'] = '';

        C('TOKEN_ON',false);
        $r = D('FilePic')->createAdd($file_data);
        if($r === false){
            E(D('FilePic')->getError());
        }
        else{
//            if($file_data['security'] == 1){
//                $ali_oss = new AliyunOss();
//                $file_data['url'] = $ali_oss->getOssClient($body_arr['upload_type'])->signUrl($body_arr['filename'], 60);
//            }
            $this->ajaxReturn(array('file_id' => $r, 'file_url' => $file_data['url']));
        }
    }

    public function policyGet($type){
        $callbackUrl = U('/extends/TengxunCos/callBack',['type'=>$type],true,true);

        $secretId = env("COS_SECRETID"); //"云 API 密钥 SecretId";
        $secretKey = env("COS_SECRETKEY"); //"云 API 密钥 SecretKey";

        $config = C('UPLOAD_TYPE_' . strtoupper($type));
        $host = $config['cos_host']; //"";
        $dir=$this->_genCosObjectName($config);

        $method = strtoupper('post');
        $method = $method ? $method : 'POST';
        $pathname=$dir;
        substr($pathname, 0, 1) != '/' && ($pathname = '/' . $pathname);
        $queryParams = array();
        $headers = array();

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

        $this->ajaxReturn([
            'url'=>$host.$pathname,
            'authorization'=>$authorization,
            'params'=>[
                'key'=>$dir,
                'success_action_redirect'=>$callbackUrl,
            ]
        ]);
    }

    private function _genCosObjectName($config, $ext = ''){
        $sub_name = self::_getName($config['subName']);
        $pre_path = $config['rootPath'] . $config['savePath'] . $sub_name .'/';
        $save_name = self::_getName($config['saveName']);
        $dir = trim(trim($pre_path . $save_name, '.'), '/');
        if($ext){
            $dir .= $ext;
        }
        return $dir;
    }

    private function _getName($rule){
        $name = '';
        if(is_array($rule)){ //数组规则
            $func     = $rule[0];
            $param    = (array)$rule[1];
            $name = call_user_func_array($func, $param);
        } elseif (is_string($rule)){ //字符串规则
            if(function_exists($rule)){
                $name = call_user_func($rule);
            } else {
                $name = $rule;
            }
        }
        return $name;
    }

}