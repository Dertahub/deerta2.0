window.UMEDITOR_HOME_URL = Config.__CDN__ + "/assets/addons/umeditor/";
require.config({
    paths: {
        'umeditor': '../addons/umeditor/umeditor.min',
        'umeditor.config': '../addons/umeditor/umeditor.config',
        'umeditor.lang': '../addons/umeditor/lang/zh-cn/zh-cn',
        'dompurify': '../addons/umeditor/third-party/dompurify',
    },
    shim: {
        'umeditor': {
            deps: [
                'umeditor.config',
                'css!../addons/umeditor/themes/default/css/umeditor.min.css'
            ],
            exports: 'UM',
        },
        'umeditor.lang': ['umeditor']
    }
});

require(['form', 'upload'], function (Form, Upload) {
    var getFileFromBase64, uploadFiles;
    uploadFiles = async function (files, callback) {
        var self = this;
        for (var i = 0; i < files.length; i++) {
            try {
                await new Promise(function (resolve) {
                    var url, html, file;
                    file = files[i];
                    Upload.api.send(file, function (data) {
                        url = Config.umeditor.fullmode ? Fast.api.cdnurl(data.url, true) : Fast.api.cdnurl(data.url);
                        if (typeof callback === 'function') {
                            callback.call(this, url, data)
                        } else {
                            if (file.type.indexOf("image") !== -1) {
                                self.execCommand('insertImage', {
                                    src: url,
                                    title: file.name || "",
                                });
                            } else {
                                self.execCommand('link', {
                                    href: url,
                                    title: file.name || "",
                                    target: '_blank'
                                });
                            }
                        }
                        resolve();
                    }, function () {
                        resolve();
                    });
                });
            } catch (e) {

            }
        }
    };
    getFileFromBase64 = function (data, url) {
        var arr = data.split(','), mime = arr[0].match(/:(.*?);/)[1],
            bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        var filename, suffix;
        if (typeof url != 'undefined') {
            var urlArr = url.split('.');
            filename = url.substr(url.lastIndexOf('/') + 1);
            suffix = urlArr.pop();
        } else {
            filename = Math.random().toString(36).substring(5, 15);
        }
        if (!suffix) {
            suffix = data.substring("data:image/".length, data.indexOf(";base64"));
        }

        var exp = new RegExp("\\." + suffix + "$", "i");
        filename = exp.test(filename) ? filename : filename + "." + suffix;
        var file = new File([u8arr], filename, {type: mime});
        return file;
    };

    //监听上传文本框的事件
    $(document).on("edui.file.change", ".edui-image-file", function (e, up, me, input, callback) {
        uploadFiles.call(me.editor, this.files, function (url, data) {
            me.uploadComplete(JSON.stringify({url: url, state: "SUCCESS"}));
        });
        up.updateInput(input);
        me.toggleMask("上传中....");
        callback && callback();
    });
    var _bindevent = Form.events.bindevent;
    Form.events.bindevent = function (form) {
        _bindevent.apply(this, [form]);
        require(['umeditor', 'umeditor.lang', 'dompurify'], function (UME, undefined, DOMPurify) {

            // 添加 hook 过滤 iframe 来源
            DOMPurify.addHook('uponSanitizeElement', function (node, data, config) {
                if (data.tagName === 'iframe') {
                    var allowedIframePrefixes = Config.umeditor.allowiframeprefixs || [];
                    var src = node.getAttribute('src');

                    // 判断是否匹配允许的前缀
                    var isAllowed = false;
                    for (var i = 0; i < allowedIframePrefixes.length; i++) {
                        if (src && src.indexOf(allowedIframePrefixes[i]) === 0) {
                            isAllowed = true;
                            break;
                        }
                    }

                    if (!isAllowed) {
                        // 不符合要求则移除该节点
                        return node.parentNode.removeChild(node);
                    }

                    // 添加安全属性
                    node.setAttribute('allowfullscreen', '');
                    node.setAttribute('allow', 'fullscreen');
                }
            });

            //重写编辑器加载
            UME.plugins['autoupload'] = function () {
                var that = this;
                that.addListener('ready', function () {
                    if (window.FormData && window.FileReader) {
                        that.getOpt('pasteImageEnabled') && that.$body.on('paste', function (event) {
                            var originalEvent;
                            originalEvent = event.originalEvent;
                            if (originalEvent.clipboardData && originalEvent.clipboardData.files.length > 0) {
                                uploadFiles.call(that, originalEvent.clipboardData.files);
                                return false;
                            }
                        });
                        that.getOpt('dropFileEnabled') && that.$body.on('drop', function (event) {
                            var originalEvent;
                            originalEvent = event.originalEvent;
                            if (originalEvent.dataTransfer && originalEvent.dataTransfer.files.length > 0) {
                                uploadFiles.call(that, originalEvent.dataTransfer.files);
                                return false;
                            }
                        });

                        //取消拖放图片时出现的文字光标位置提示
                        that.$body.on('dragover', function (e) {
                            if (e.originalEvent.dataTransfer.types[0] == 'Files') {
                                return false;
                            }
                        });
                    }
                });

            };
            $.extend(window.UMEDITOR_CONFIG.whiteList, {
                div: ['style', 'class', 'id', 'data-tpl', 'data-source', 'data-id'],
                span: ['style', 'class', 'id', 'data-id']
            });
            $(Config.umeditor.classname || '.editor', form).each(function () {
                var id = $(this).attr("id");
                if (!id) {
                    id = "umeditor_" + Math.random().toString(36).substring(2, 6);
                    $(this).attr("id", id);
                }
                $(this).removeClass('form-control');
                var options = $(this).data("umeditor-options");
                UME.list[id] = UME.getEditor(id, $.extend(true, {}, {
                    initialFrameWidth: '100%',
                    zIndex: 90,
                    autoHeightEnabled: Config.umeditor.autoHeightEnabled,
                    toolbar: [
                        Config.umeditor.toolbar
                    ],
                    minFrameHeight: Config.umeditor.minFrameHeight,
                    initialFrameHeight: Config.umeditor.initialFrameHeight,
                    xssFilterRules: false,
                    outputXssFilter: false,
                    inputXssFilter: false,
                    autoFloatEnabled: false,
                    pasteImageEnabled: true,
                    dropFileEnabled: true,
                    fontfamily: [
                        {name: 'songti', val: '宋体,SimSun'},
                        {name: 'yahei', val: '微软雅黑,Microsoft YaHei'},
                        {name: 'kaiti', val: '楷体,楷体_GB2312, SimKai'},
                        {name: 'heiti', val: '黑体, SimHei'},
                        {name: 'lishu', val: '隶书, SimLi'},
                        {name: 'andaleMono', val: 'andale mono'},
                        {name: 'arial', val: 'arial, helvetica,sans-serif'},
                        {name: 'arialBlack', val: 'arial black,avant garde'},
                        {name: 'comicSansMs', val: 'comic sans ms'},
                        {name: 'impact', val: 'impact,chicago'},
                        {name: 'timesNewRoman', val: 'times new roman'},
                        {name: 'sans-serif', val: 'sans-serif'}
                    ],
                    fontsize: [12, 14, 16, 18, 24, 32, 48],
                    paragraph: {'p': '', 'h1': '', 'h2': '', 'h3': '', 'h4': '', 'h5': '', 'h6': ''},
                    baiduMapKey: Config.umeditor.baidumapkey || '',
                    baiduMapCenter: Config.umeditor.baidumapcenter || '',
                    imageUrl: '',
                    imagePath: '',
                    initCallback: function () {
                        var purifyOptions = {
                            ADD_TAGS: ['iframe'],
                            //允许_url属性
                            ADD_ATTR: ['_url'],
                            FORCE_REJECT_IFRAME: false
                        };
                        this.addListener('beforesetcontent', function (event, html) {
                            html = DOMPurify.sanitize(html, purifyOptions);
                            return html;
                        });
                        this.addListener('beforeinserthtml', function (event, html) {
                            return DOMPurify.sanitize(html, purifyOptions);
                        })
                    },
                    imageUploadCallback: function (file, fn) {
                        var me = this;
                        Upload.api.send(file, function (data) {
                            var url = data.url;
                            fn && fn.call(me, url, data);
                        });
                    }
                }, options || {}));
            });
        });
    }
});
