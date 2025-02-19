$(function() {
    // 生成卡密按钮点击事件
    $('.btn-generate-card').click(function() {
        $.ajax({
            url: 'card/generate',
            type: 'post',
            dataType: 'json',
            success: function(res) {
                if (res.code == 1) {
                    // 显示生成的卡密
                    layer.alert('生成的卡密为：' + res.data.card_no, {
                        title: '卡密生成成功',
                        icon: 1
                    });
                } else {
                    layer.alert(res.msg, {icon: 2});
                }
            }
        });
    });

    // 导出卡密
    $('.btn-export-card').click(function() {
        layer.confirm('确定要导出卡密吗？', function(index) {
            window.location.href = 'card/export';
            layer.close(index);
        });
    });

    // 批量删除
    $('.btn-del-card').click(function() {
        var ids = Table.api.selectedids();
        if (ids.length == 0) {
            layer.msg('请选择要删除的卡密');
            return;
        }
        layer.confirm('确定要删除选中的卡密吗？', function(index) {
            $.ajax({
                url: 'card/del',
                type: 'post',
                data: {ids: ids},
                dataType: 'json',
                success: function(res) {
                    if (res.code == 1) {
                        layer.msg('删除成功');
                        Table.api.reload();
                    } else {
                        layer.alert(res.msg, {icon: 2});
                    }
                }
            });
            layer.close(index);
        });
    });

    // 查看使用记录
    $('.btn-view-log').click(function() {
        var id = $(this).data('id');
        layer.open({
            type: 2,
            title: '卡密使用记录',
            shadeClose: true,
            shade: 0.8,
            area: ['90%', '90%'],
            content: 'card/log?id=' + id
        });
    });
}); 