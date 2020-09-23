

function cos_set_param_upload(up,filename,ret,policyGetUrl,file){

    $.get(policyGetUrl+'?title='+encodeURIComponent(filename)).then(function (res){

        if (up.runtime === 'html4') {
            up.setOption('url', res.url + '?sign=' + encodeURIComponent(authorization));
        } else {
            up.setOption('url', res.url);
            var headers = up.setOption('headers') || {};
            headers.Authorization = res.authorization;
            up.setOption('headers', headers);
        }

        // 指定文件名
        res.params.name=filename;
        res.params.success_action_redirect+='?title='+encodeURIComponent(filename);
        res.params['Content-Type']=file.type;
        console.log(res.params);
        up.setOption('multipart_params', res.params);
        up.start();
    });

}