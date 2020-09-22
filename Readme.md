# FormBuilder腾讯云cos上传组件

## 用法
### 1.安装及配置
#### 1.1安装
```shell script
composer install quansitech/qscmf-formitem-tengxun-cos
```
#### 1.2在env文件中加入
```dotenv
COS_SECRETID=[腾讯云api secretid]
COS_SECRETKEY=[腾讯云api secretkey]
COS_HOST=[cos存储地址]
```

#### 1.3在config.php中对应的上传类型更改配置
示例
```php
'UPLOAD_TYPE_IMAGE' => array(
    'mimes'    => 'image/jpeg,image/png,image/gif,image/bmp', //允许上传的文件MiMe类型
    'maxSize'  => 5*1024*1024, //上传的文件大小限制 (0-不做限制)
    'exts'     => 'jpg,gif,png,jpeg', //允许上传的文件后缀
    'autoSub'  => true, //自动子目录保存文件
    'subName'  => array('date','Ymd'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
    'rootPath' => './Uploads/', //保存根路径
    'savePath' => 'image/', //保存路径
    'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
    'saveExt'  => '', //文件保存后缀，空则使用原后缀
    'replace'  => false, //存在同名是否覆盖
    'hash'     => true, //是否生成hash编码
    'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    'cos_host' => env('COS_HOST'),
),
```

### 2.使用
```php
$builder=new FormBuilder();
$builder
    ->addFormItem('picture_cos1','picture_cos','单图Cos1')
    ->addFormItem('pictures_cos1','pictures_cos','多图Cos1')
    ->addFormItem('file_cos1','file_cos','单文件Cos1')
    ->addFormItem('files_cos1','files_cos','多文件Cos1')
    ->display();
```
默认采用image和file类型上传，可通过定义data-url参数来设置需要的类型
