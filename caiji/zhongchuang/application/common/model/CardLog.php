<?php

namespace app\common\model;

use think\Model;

class CardLog extends Model
{
    // 表名
    protected $name = 'card_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 关联卡密
    public function card()
    {
        return $this->belongsTo('Card', 'card_id')->setEagerlyType(0);
    }

    // 关联用户
    public function user()
    {
        return $this->belongsTo('User', 'user_id')->setEagerlyType(0);
    }
} 