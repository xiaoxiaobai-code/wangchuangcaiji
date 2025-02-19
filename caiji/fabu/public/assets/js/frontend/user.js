define(['jquery', 'bootstrap', 'frontend', 'form', 'template'], function ($, undefined, Frontend, Form, Template) {
    var Controller = {
        released_config: function () {
            Form.api.bindevent($('#release-form'), null, function(form) {
                // 阻止默认提交
                var formData = {
                    theme_settings: {
                        theme: $('#theme').val(),
                        publish_key: $('#publish_key').val()
                    },
                    price_settings: {
                        pay_mode: $('input[name="pay_mode"]:checked').val(),
                        normal_price: $('#normal_price').val(),
                        vip_price: $('#vip_price').val(),
                        svip_price: $('#svip_price').val()
                    },
                    category_mapping: {
                        zhongchuang: $('#zhongchuang').val(),
                        maopao: $('#maopao').val(),
                        fuyuan: $('#fuyuan').val()
                    }
                };

                // 发送Ajax请求
                $.ajax({
                    url: 'user/released_config',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        released_config: JSON.stringify(formData),
                        __token__: $("input[name='__token__']").val(),
                        ajax: 1
                    },
                    success: function(ret) {
                        if (ret.code === 1) {
                            Layer.msg(ret.msg || '保存成功', {icon: 1});
                            setTimeout(function(){
                                location.reload();
                            }, 1500);
                        } else {
                            Layer.msg(ret.msg || '保存失败', {icon: 2});
                        }
                    },
                    error: function() {
                        Layer.msg('网络错误，请稍后重试', {icon: 2});
                    }
                });
                
                return false; // 阻止表单默认提交
            });
        }
    };
    return Controller;
}); 