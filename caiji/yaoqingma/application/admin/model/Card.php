<?php
namespace app\admin\model;

use think\Model;

class Card extends Model
{
    // 表名
    protected $name = 'card';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'type_text'
    ];

    // 插入时自动写入的字段
    protected $insert = ['agent_id'];

    // 卡密状态
    public function getStatusList()
    {
        return [0 => '未使用', 1 => '已使用'];
    }

    // 卡密类型
    public function getTypeList()
    {
        return [1 => '7天', 2 => '30天', 3 => '90天'];
    }

    // 获取卡密状态文本
    public function getStatusTextAttr($value, $data)
    {
        $status = $this->getStatusList();
        return isset($status[$data['status']]) ? $status[$data['status']] : '';
    }

    // 获取卡密类型文本
    public function getTypeTextAttr($value, $data)
    {
        $type = $this->getTypeList();
        return isset($type[$data['type']]) ? $type[$data['type']] : '';
    }

    // 关联使用记录
    public function logs()
    {
        return $this->hasMany('CardLog', 'card_id');
    }

    // 关联代理
    public function agent()
    {
        return $this->belongsTo('Admin', 'agent_id');
    }
} 