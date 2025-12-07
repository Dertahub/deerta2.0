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
