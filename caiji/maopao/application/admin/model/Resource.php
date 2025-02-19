<?php

namespace app\admin\model;

use think\Model;

class Resource extends Model
{
    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 定义时间戳字段名
    protected $createTime = 'createTime';
    protected $updateTime = 'updateTime';
} 