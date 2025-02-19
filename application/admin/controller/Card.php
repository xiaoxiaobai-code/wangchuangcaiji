<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 卡密管理
 *
 * @icon fa fa-circle-o
 */
class Card extends Backend
{

    /**
     * Card模型对象
     * @var \app\admin\model\Card
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Card;

    }
	    // 添加批量生成卡密功能
    public function batchGenerate()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $count = intval($params['count'] ?? 0);
          
            
            if ($count <= 0 || $count > 1000) {
                $this->error('生成数量必须在1到1000之间');
            }

            $data = [];
            for ($i = 0; $i < $count; $i++) {
                $data[] = [
					'type' => intval($params['type'] ?? 1) ,
                    'card_no' => $this->generateCardNo(),
                    'status' => 0, // 默认状态为可用
                    'createtime' => time()
                ];
            }

            $this->model->saveAll($data);
            $this->success('成功生成'.$count.'张卡密');
        }
        return $this->view->fetch();
    }
	    // 生成随机卡密
    private function generateCardNo()
    {
        return strtoupper(md5(uniqid().mt_rand(10000,99999)));
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


}
