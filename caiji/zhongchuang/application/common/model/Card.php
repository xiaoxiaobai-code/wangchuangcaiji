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
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 追加属性
    protected $append = [
        'type_text',
        'status_text'
    ];
    
    // 类型列表
    public static $typeList = [
        1 => '月卡',
        2 => '季卡',
        3 => '年卡'
    ];

    // 状态列表
    public static $statusList = [
        0 => '未使用',
        1 => '已使用'
    ];

    public function getTypeTextAttr($value, $data)
    {
        return isset(self::$typeList[$data['type']]) ? self::$typeList[$data['type']] : '';
    }

    public function getStatusTextAttr($value, $data)
    {
        return isset(self::$statusList[$data['status']]) ? self::$statusList[$data['status']] : '';
    }

    // 关联创建者
    public function creator()
    {
        return $this->belongsTo('Admin', 'creator_id')->setEagerlyType(0);
    }

    // 关联使用记录
    public function log()
    {
        return $this->hasOne('CardLog', 'card_id');
    }
} 