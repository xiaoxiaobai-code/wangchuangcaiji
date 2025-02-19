<?php
namespace app\common\model;

use think\Model;

class Card extends Model
{
    // 表名
    protected $name = 'card';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
    protected $append = [
        'status_text'
    ];

    // 状态列表
    public function getStatusList()
    {
        return ['0' => '未使用', '1' => '已使用'];
    }

    // 获取状态文本
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }
} 