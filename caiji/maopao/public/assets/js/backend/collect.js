define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'collect/index',
                }
            });
            
            var table = $("#table");
            
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        {field: 'title', title: '标题'},
                        {field: 'url', title: '链接', formatter: function(value, row, index) {
                            return '<a href="' + value + '" target="_blank">' + value + '</a>';
                        }}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            
            // 添加采集按钮事件
            $(document).on("click", ".btn-refresh", function () {
                $.ajax({
                    url: 'collect/index',
                    type: 'post',
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code === 1) {
                            Toastr.success(ret.msg);
                            table.bootstrapTable('refresh');
                        } else {
                            Layer.alert(ret.msg);
                            console.log('Debug信息:', ret.debug);
                            console.log('HTML样本:', ret.html_sample);
                        }
                    },
                    error: function (xhr) {
                        Layer.alert('采集失败，请检查服务器日志');
                        console.log('错误信息:', xhr.responseText);
                    }
                });
            });
        }
    };
    return Controller;
}); 