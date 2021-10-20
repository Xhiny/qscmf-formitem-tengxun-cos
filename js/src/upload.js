import React, { useEffect, useState, useRef } from 'react';
import { Upload } from 'antd';
import ReactDOM from "react-dom";
import ImgCrop from 'antd-img-crop';

import "antd/dist/antd.css";

const initCosData = {
    url: '',
    params: { key: '', success_action_redirect: ''},
    authorization: ''
};

const getExt = (path) => {
    const parts = path.split('.');
    if(parts.length>1){
        return '.' + parts[parts.length - 1];
    }
    else{
        return '';
    }
}

function Uploader({value, maxCount, listType = "picture-card",showUploadList, crop, policyUrl, onChange, children }){
    const [ cosData, setCosData ] = useState(initCosData);
    const [ fileList, setFileList ] = useState([]);
    const [ key, setKey ] = useState('');
    const uploadRef = useRef();
    
    useEffect(() => {
        const files = value.map(item => {
            const { name, thumbUrl, ...response} = item;

            return {
                name: name,
                percent: 100,
                status: 'done',
                thumbUrl,
                url: item.file_url,
                response
            }
        })
        setFileList(files);
    }, [value])

    const render = () => {
        if(crop){
            return <ImgCrop {...crop}>
                {renderUpload()}
            </ImgCrop>
        }
        else{
            return renderUpload()
        }
    }

    const renderUpload = () => {

        const props = {
            action: cosData.url,
            listType: listType,
            fileList: fileList,
            maxCount: maxCount,
            showUploadList: showUploadList,
            data: {
                key: key,
                success_action_status: 200,
                Signature: cosData.authorization,
                'Content-Type': '',
                success_action_redirect: cosData.params.success_action_redirect
            },
            onChange: ({ file: file, fileList: newFileList }) => {
                setFileList(newFileList);
                const uploadList = newFileList.filter(item => item.status !== 'error' && item.status !== 'done');
                if(onChange && uploadList.length === 0){
                    onChange(newFileList.map(item => {
                        return {
                            status: item.status,
                            response: item.response
                        }
                    }));
                }
            },
            beforeUpload: async (file) => {
                const res = await fetch(policyUrl);
                const result = await res.json();
                setCosData(result);
                setKey(`${result.params.key}${getExt(file.name)}`)
            }
        }

        return <Upload ref={uploadRef} {...props}>{children}</Upload>
    }

    return render();
}


function CosUploader(obj, opt){
    const defaultOpt = {
        value: [],
        listType: 'picture-card',
        policyUrl: '/extends/TengxunCos/policyGet/type/image',
        maxCount: 1,
        showUploadList: true,
        crop: false,
        onChange: () => {}
    };
    Object.assign(defaultOpt, opt);
    ReactDOM.render(<Uploader {...defaultOpt}><div dangerouslySetInnerHTML={{__html: obj.innerHTML}}></div></Uploader>, obj);
}

window.CosUploader = CosUploader;

