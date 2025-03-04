<?php
namespace app\admin\model;

use think\Model;

class CardLog extends Model
{
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    
    // 定义时间戳字段名
    protected $createTime = 'usetime';
    protected $updateTime = false;
} 