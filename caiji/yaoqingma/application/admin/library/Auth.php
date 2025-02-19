<?php

namespace app\admin\library;

class Auth
{
    public function isAgent()
    {
        return in_array('agent', $this->getGroups());
    }

    /**
     * 根据账号获取用户信息
     * @param string $account 账号
     * @return array|false
     */
    public function getUserByAccount($account)
    {
        return \app\common\model\User::where('username', $account)
            ->whereOr('email', $account)
            ->whereOr('mobile', $account)
            ->find();
    }
} 