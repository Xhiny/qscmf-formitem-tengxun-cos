<?php


namespace FormItem\TengxunCos;


use Bootstrap\LaravelProvider;
use Bootstrap\Provider;
use Bootstrap\RegisterContainer;
use FormItem\TengxunCos\FormType\FileCos\FileCos;
use FormItem\TengxunCos\Controller\TengxunCosController;
use FormItem\TengxunCos\FormType\FilesCos\FilesCos;
use FormItem\TengxunCos\FormType\PictureCos\PictureCos;
use FormItem\TengxunCos\FormType\PicturesCos\PicturesCos;

class TengxunCosProvider implements Provider,LaravelProvider
{
    public function register()
    {
        RegisterContainer::registerFormItem('picture_cos', PictureCos::class);
        RegisterContainer::registerFormItem('pictures_cos', PicturesCos::class);
        RegisterContainer::registerFormItem('file_cos', FileCos::class);
        RegisterContainer::registerFormItem('files_cos', FilesCos::class);

        RegisterContainer::registerSymLink(WWW_DIR . '/Public/tengxun-cos', __DIR__ . '/../asset/tengxun-cos');
        RegisterContainer::registerSymLink(WWW_DIR . '/Public/tengxun-cos-uploader', __DIR__ . '/../js/dist');
        RegisterContainer::registerController('extends','TengxunCos',TengxunCosController::class);
    }

    public function registerLara()
    {
    }
}