define(['jquery', 'bootstrap', 'frontend', 'form'], function ($, undefined, Frontend, Form) {
    var Controller = {
        index: function () {
            Form.api.bindevent($('#release-form'), function(data, ret){
                if (ret.code === 1) {
                    Layer.msg(ret.msg, {icon: 1});
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                } else {
                    Layer.msg(ret.msg, {icon: 2});
                }
            });

            // 表单提交前处理
            $('#release-form').on('submit', function(e){
                e.preventDefault();
                
                // 构建JSON数据
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
                    url: 'user/released/save',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        released_config: JSON.stringify(formData)
                    },
                    success: function(response) {
                        if (response.code === 1) {
                            Layer.msg('保存成功', {icon: 1});
                            setTimeout(function(){
                                location.reload();
                            }, 1500);
                        } else {
                            Layer.msg(response.msg || '保存失败', {icon: 2});
                        }
                    },
                    error: function() {
                        Layer.msg('网络错误，请稍后重试', {icon: 2});
                    }
                });
            });
        }
    };
    return Controller;
}); 