<?php

namespace app\admin\controller;

use app\common\controller\Backend;

class Cardlog extends Backend
{
    protected $model = null;
    protected $searchFields = 'card.card_no,user.username';
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('CardLog');
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 如果不是超级管理员，只能看到自己生成的卡密的使用记录
            if (!$this->auth->isSuperAdmin()) {
                $where['card.creator_id'] = $this->auth->id;
            }

            $list = $this->model
                ->with(['card', 'user'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $this->model->where($where)->count(), "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
} 