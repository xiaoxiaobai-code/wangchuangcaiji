<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use fast\Random;

class Card extends Backend
{
    protected $model = null;
    protected $cardLogModel = null;
    protected $searchFields = 'card_no';
    protected $noNeedRight = ['generate']; // 无需鉴权的方法

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Card');
        $this->cardLogModel = model('CardLog');
    }

    // 卡密列表
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            // 如果不是超级管理员，只能看到自己生成的卡密
            if (!$this->auth->isSuperAdmin()) {
                $where['creator_id'] = $this->auth->id;
            }

            $list = $this->model
                ->with(['creator', 'log.user'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $this->model->where($where)->count(), "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    // 生成卡密
    public function generate()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $num = intval($params['num']);
            $type = intval($params['type']);
            
            if ($num <= 0 || $num > 100) {
                $this->error('生成数量必须在1-100之间');
            }
            
            Db::startTrans();
            try {
                for ($i = 0; $i < $num; $i++) {
                    $this->model->save([
                        'card_no' => strtoupper(Random::alnum(16)),
                        'type' => $type,
                        'status' => 0,
                        'creator_id' => $this->auth->id,
                        'creator_type' => $this->auth->isSuperAdmin() ? 1 : 2,
                        'create_time' => time(),
                        'update_time' => time()
                    ]);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('生成成功');
        }
        return $this->view->fetch();
    }
} 