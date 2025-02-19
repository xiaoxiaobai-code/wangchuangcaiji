<?php

namespace app\admin\model;

use think\Model;

class User extends Model
{
    public function getAgentList()
    {
        return $this->where('group_id', 2) // 假设代理的group_id是2
            ->field('id,username')
            ->select();
    }
} 