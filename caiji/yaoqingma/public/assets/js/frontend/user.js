$(function() {
    // 卡密输入框失去焦点时验证
    $('input[name="card_no"]').blur(function() {
        var card_no = $(this).val();
        if (card_no.length > 0) {
            $.ajax({
                url: 'user/checkcard',
                type: 'post',
                data: {card_no: card_no},
                dataType: 'json',
                success: function(res) {
                    if (res.code == 0) {
                        layer.tips(res.msg, $('input[name="card_no"]'), {tips: [1, '#ff5722']});
                    }
                }
            });
        }
    });

    // 卡密验证
    $('.btn-check-card').click(function() {
        var card_no = $('input[name="card_no"]').val();
        if (card_no.length == 0) {
            layer.msg('请输入卡密');
            return;
        }
        $.ajax({
            url: 'user/checkcard',
            type: 'post',
            data: {card_no: card_no},
            dataType: 'json',
            success: function(res) {
                if (res.code == 1) {
                    layer.msg(res.msg, {icon: 1});
                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            }
        });
    });
}); 