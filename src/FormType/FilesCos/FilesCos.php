<?php
namespace FormItem\TengxunCos\FormType\FilesCos;

use Illuminate\Support\Str;
use Qscmf\Builder\FormType\FormType;
use Think\View;

class FilesCos implements FormType{

    public function build(array $form_type){
        $view = new View();
        $view->assign('form', $form_type);
        $view->assign('gid', Str::uuid());
        $content = $view->fetch(__DIR__ . '/files_cos.html');
        return $content;
    }
}