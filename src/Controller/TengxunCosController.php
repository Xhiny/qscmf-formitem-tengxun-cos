<?php


namespace FormItem\TengxunCos\Controller;


use FormItem\TengxunCos\TengxunCos;
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

        $header=TengxunCos::getInstance()->headObj($config['cos_host'],I('get.key'));

        if (!$header){
            header("HTTP/1.1 403 Forbidden");
            echo '上传的文件找不到';
            return;
        }

        if(!empty($config['mimes'])){
            $mimes = explode(',', $config['mimes']);
            if(!in_array(strtolower($header['ContentType']), $mimes)){
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

        $file_data['url'] = $config['cos_host'] . '/' . I('get.key');
        $file_data['size'] = $header['ContentLength'];
        $file_data['cate'] = I('get.type');
        $file_data['security'] = $config['security'] ? 1 : 0;
        $file_data['file'] = '';

        C('TOKEN_ON',false);
        $r = D('FilePic')->createAdd($file_data);
        if($r === false){
            E(D('FilePic')->getError());
        }
        else{
            if($file_data['security'] == 1){
                $file_data['url'] = TengxunCos::getInstance()->getFileUrl($r);
            }
            $this->ajaxReturn(array('file_id' => $r, 'file_url' => $file_data['url']));
        }
    }

    public function policyGet($type){
        $callbackUrl = U('/extends/TengxunCos/callBack',['type'=>$type],true,true);

        $config = C('UPLOAD_TYPE_' . strtoupper($type));
        $host = $config['cos_host']; //"";
        $dir=$this->_genCosObjectName($config);

        $pathname=$dir;
        substr($pathname, 0, 1) != '/' && ($pathname = '/' . $pathname);

        $authorization=TengxunCos::getInstance()->getAuthorization($pathname,'POST');

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