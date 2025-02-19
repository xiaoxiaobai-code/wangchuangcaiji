<?php

namespace app\admin\controller;

use app\common\controller\Backend;

class Resource extends Backend
{
    protected $model = null;
    
    // 是否开启数据限制
    protected $dataLimit = true;
    
    // 数据限制字段
    protected $dataLimitField = 'admin_id';
    
    // 排除字段
    protected $noNeedRight = ['index'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Resource');
    }
} 