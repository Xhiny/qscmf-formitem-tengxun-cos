<?php
namespace FormItem\TengxunCos\FormType\FileCos;

use Illuminate\Support\Str;
use Qscmf\Builder\FormType\FormType;
use Think\View;

class FileCos implements FormType{

    public function build(array $form_type){
        $view = new View();
        $view->assign('form', $form_type);
        $view->assign('gid', Str::uuid());
        $content = $view->fetch(__DIR__ . '/file_cos.html');
        return $content;
    }
}