define([], function () {
    require.config({
    paths: {
        'ajcaptcha-verify': '../addons/ajcaptcha/js/verify',
        'crypto-js': '../addons/ajcaptcha/js/crypto-js'
    },
    shim: {
        'ajcaptcha-verify': {
            deps: [
                'jquery', 'crypto-js',
                'css!../addons/ajcaptcha/css/verify.css'
            ],
        }
    }
});

require(['form'], function (Form) {
    window.ajcaptcha = function (captcha) {
        require(['ajcaptcha-verify'], function (undefined) {
            captcha = captcha ? captcha : $("input[name=captcha]");
            if (captcha.length > 0) {
                var form = captcha.closest("form");
                var parentDom = captcha.parent();
                // 非文本验证码
                if ($("a[data-event][data-url]", parentDom).length > 0) {
                    return;
                }
                var container = captcha.closest("form");
                container = $("<div />").addClass("ajcaptcha-container");
                if (Config.ajcaptcha.captchaMode === 'fixed') {
                    container.css("margin-bottom", "15px");
                }
                if (captcha.parentsUntil(form, "div.form-group").length > 0) {
                    captcha.parentsUntil(form, "div.form-group").addClass("hidden");
                    container.insertAfter(captcha.parentsUntil(form, "div.form-group"));
                } else if (parentDom.is("div.input-group")) {
                    parentDom.addClass("hidden");
                    container.insertAfter(parentDom);
                } else {
                    container.appendTo(form);
                }
                var imgSizeWidth, imgSizeHeight;
                imgSizeWidth = Math.min(500, Math.max(310, container.width()));
                imgSizeHeight = imgSizeWidth / 2;

                captcha.attr("data-rule", "required");
                // 验证失败时进行操作
                captcha.on('invalid.field', function (e, result, me) {
                    //必须删除errors对象中的数据，否则会出现Layer的Tip
                    delete me.errors['captcha'];
                    if (!captcha.data("ajcaptcha")) {
                        render();
                    } else {
                        container.find(".mask").show();
                    }
                });

                var render = function () {
                    let baseUrl = Fast.api.fixurl("/addons/ajcaptcha");
                    let options = {
                        baseUrl: baseUrl,
                        mode: Config.ajcaptcha.captchaMode || 'pop',     //展示模式
                        containerId: 'use-undefined-id', //pop模式 必填 被点击之后出现行为验证码的元素id
                        imgSize: {       //图片的大小对象,有默认值{ width: '310px',height: '155px'},可省略
                            width: imgSizeWidth + 'px',
                            height: imgSizeHeight + 'px',
                        },
                        barSize: {          //下方滑块的大小对象,有默认值{ width: '310px',height: '50px'},可省略
                            width: imgSizeWidth + 'px',
                            height: '50px',
                        },
                        ready: function () {
                            if (!Config.ajcaptcha.preRender) {
                                form.find(".mask").show();
                            }
                        },  //加载完毕的回调
                        success: function (params) { //成功的回调
                            // params为返回的二次验证参数 需要在接下来的实现逻辑回传服务器
                            captcha.val(params.captchaVerification);
                            form.trigger("submit");
                        },
                        error: function () {
                        }        //失败的回调
                    };
                    let obj, captchaType = Config.ajcaptcha.captchaType;
                    if (captchaType === 'blockPuzzle') {
                        obj = container.slideVerify(options);
                    } else {
                        obj = container.pointsVerify(options);
                    }
                    captcha.data("ajcaptcha", obj);
                }

                // 预渲染
                if (Config.ajcaptcha.preRender) {
                    render();
                }

                // 监听表单错误事件
                form.on("error.form", function (e, data) {
                    captcha.val('');
                });
            }
        });
    };
    // ajcaptcha($("input[name=captcha]"));

    if (typeof Frontend !== 'undefined') {
        Frontend.api.preparecaptcha = function (btn, type, data) {
            require(['form'], function (Form) {
                $("#ajcaptchacontainer").remove();
                $("<div />").attr("id", "ajcaptchacontainer").addClass("hidden").html(Template("captchatpl", {})).appendTo("body");
                var form = $("#ajcaptchacontainer form");
                form.data("validator-options", {
                    valid: function (ret) {
                        data.captcha = $("input[name=captcha]", form).val();
                        Frontend.api.sendcaptcha(btn, type, data, function (data, ret) {
                            console.log("ok");
                        });
                        return true;
                    }
                })
                Form.api.bindevent(form);
            });
        };
    }

    var _bindevent = Form.events.bindevent;
    Form.events.bindevent = function (form) {
        _bindevent.apply(this, [form]);
        var captchaObj = $("input[name=captcha]", form);
        if (captchaObj.length > 0) {
            captchaObj.closest("form").find("button[type=submit]").removeAttr("disabled");
            ajcaptcha(captchaObj);
            if ($(form).attr("name") === 'captcha-form') {
                setTimeout(function () {
                    captchaObj.trigger("invalid.field", [{key: 'captcha'}, {errors: {}}]);
                }, 100);
            }
        }
    }
});

require(['fast', 'layer'], function (Fast, Layer) {
    var _fastOpen = Fast.api.open;
    Fast.api.open = function (url, title, options) {
        options = options || {};
        options.area = Config.betterform.area;
        options.offset = Config.betterform.offset;
        options.anim = Config.betterform.anim;
        options.shadeClose = Config.betterform.shadeClose;
        options.shade = Config.betterform.shade;
        return _fastOpen(url, title, options);
    };
    if (isNaN(Config.betterform.anim)) {
        var _layerOpen = Layer.open;
        Layer.open = function (options) {
            var classNameArr = {slideDown: "layer-anim-slide-down", slideLeft: "layer-anim-slide-left", slideUp: "layer-anim-slide-up", slideRight: "layer-anim-slide-right"};
            var animClass = "layer-anim " + classNameArr[options.anim] || "layer-anim-fadein";
            var index = _layerOpen(options);
            var layero = $('#layui-layer' + index);

            layero.addClass(classNameArr[options.anim] + "-custom");
            layero.addClass(animClass).one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function () {
                $(this).removeClass(animClass);
            });
            return index;
        }
    }
});
require.config({
    paths: {
        'editable': '../libs/bootstrap-table/dist/extensions/editable/bootstrap-table-editable.min',
        'x-editable': '../addons/editable/js/bootstrap-editable.min',
    },
    shim: {
        'editable': {
            deps: ['x-editable', 'bootstrap-table']
        },
        "x-editable": {
            deps: ["css!../addons/editable/css/bootstrap-editable.css"],
        }
    }
});
if ($("table.table").length > 0) {
    require(['editable', 'table'], function (Editable, Table) {
        $.fn.bootstrapTable.defaults.onEditableSave = function (field, row, oldValue, $el) {
            var data = {};
            data["row[" + field + "]"] = row[field];
            Fast.api.ajax({
                url: this.extend.edit_url + "/ids/" + row[this.pk],
                data: data
            });
        };
    });
}

if (Config.modulename == 'admin' && Config.controllername == 'index' && Config.actionname == 'index') {

    require.config({
        paths: {
            'kefu': '../addons/kefu/js/kefu'
        },
        shim: {
            'kefu': {
                deps: ['css!../addons/kefu/css/kefu_admin_default.css'],
                exports: 'KeFu'
            }
        }
    });

    require(['kefu'], function (KeFu) {
        KeFu.initialize(document.domain, 'admin');
    });

} else {

    try {
        var parentConifg = window.parent.Config;
    } catch (err) {
        var parentConifg = false;
    }

    if (parentConifg && parentConifg.modulename == 'admin') {
        // 监听后台iframe内的快捷键打开会话窗口
        $(document).on('keyup', function (event) {

            if (window.parent.KeFu) {

                // console.log('当前按钮的code-iframe内:', event.keyCode);

                // 对打开会话窗口的监听
                // 打开会话窗口快捷键[ctrl + /],若需修改，请拿到对应键的keyCode替换下一行的191即可，191代表[/]键的keyCode
                if (event.keyCode === 191 && event.ctrlKey) {

                    if (window.parent.KeFu.last_sender) {
                        if (parseInt(window.parent.KeFu.last_sender) === window.parent.KeFu.session_id) {
                            // 展开分组
                            if (!window.parent.KeFu.group_show.dialogue) {
                                $('#heading_dialogue a').click();
                            }
                        } else {
                            window.parent.KeFu.changeSession(window.parent.KeFu.last_sender);
                            window.parent.KeFu.last_sender = null;
                        }
                    } else if (window.parent.KeFu.window_is_show) {
                        window.parent.KeFu.toggle_window('hide');
                    }

                    if (!window.parent.KeFu.window_is_show) {
                        window.parent.KeFu.toggle_window('show');
                    }
                    return ;
                }
            }

        });

    } else {

        require.config({
            paths: {
                'kefu': '../addons/kefu/js/kefu'
            },
            shim: {
                'kefu': {
                    deps: ['css!../addons/kefu/css/kefu_default.css'],
                    exports: 'KeFu'
                }
            }
        });

        require(['kefu'], function (KeFu) {
            KeFu.initialize(document.domain, 'index');
        });
    }
}
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

});