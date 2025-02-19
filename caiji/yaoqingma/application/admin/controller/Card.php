<?php
namespace app\admin\controller;

use app\common\controller\Backend;

class Card extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Card;
    }

    // 重写index方法，添加代理权限过滤
    public function index()
    {
        if ($this->auth->isAgent()) {
            $this->model->where('agent_id', $this->auth->id);
        }
        return parent::index();
    }

    // 添加生成卡密功能
    public function generate()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $params['agent_id'] = $this->auth->id;
            $params['card_no'] = $this->generateCardNo();
            $this->model->save($params);
            $this->success('生成成功', null, ['card_no' => $params['card_no']]);
        }
        return $this->view->fetch();
    }

    // 查看使用记录
    public function log()
    {
        $card_id = $this->request->param('id');
        $logModel = new \app\admin\model\CardLog;
        $list = $logModel->where('card_id', $card_id)->select();
        $this->assign('list', $list);
        return $this->view->fetch();
    }

    // 生成随机卡密
    private function generateCardNo()
    {
        return strtoupper(md5(uniqid().mt_rand(10000,99999)));
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            
            // 如果当前用户是代理，自动设置 agent_id
            if ($this->auth->isAgent()) {
                $params['agent_id'] = $this->auth->id;
            } elseif (empty($params['agent_id'])) {
                $this->error('代理ID不能为空');
            }
            
            $params['card_no'] = $this->generateCardNo();
            $this->model->save($params);
            $this->success('添加成功', null, ['card_no' => $params['card_no']]);
        }
        return $this->view->fetch();
    }

    public function getAgentList()
    {
        $list = \app\admin\model\User::getAgentList();
        return json(['code' => 1, 'data' => $list]);
    }

    // 添加批量生成卡密功能
    public function batchGenerate()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $count = intval($params['count'] ?? 0);
            $agent_id = $this->auth->id;
            
            if ($count <= 0 || $count > 1000) {
                $this->error('生成数量必须在1到1000之间');
            }

            $data = [];
            for ($i = 0; $i < $count; $i++) {
                $data[] = [
                    'agent_id' => $agent_id,
                    'card_no' => $this->generateCardNo(),
                    'status' => 1, // 默认状态为可用
                    'createtime' => time()
                ];
            }

            $this->model->saveAll($data);
            $this->success('成功生成'.$count.'张卡密');
        }
        return $this->view->fetch();
    }
} 