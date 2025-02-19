<?php

namespace app\index\controller;

use think\Db;
use think\Exception;
use app\admin\model\Card;
use app\admin\model\CardLog;

class User extends \think\Controller
{
    public function register()
    {
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $email = $this->request->post('email');
            $mobile = $this->request->post('mobile');
            $card_no = $this->request->post('card_no');
            
            if (!$card_no) {
                $this->error(__('Card number can not be empty'));
            }
            
            // 验证卡密
            $card = Card::where('card_no', $card_no)
                ->where('status', 0)
                ->find();
                
            if (!$card) {
                $this->error(__('Invalid or used card number'));
            }
            
            $time = time();
            // 根据卡密类型设置到期时间
            switch ($card->type) {
                case 1: // 月卡
                    $expiretime = strtotime('+1 month', $time);
                    break;
                case 2: // 季卡
                    $expiretime = strtotime('+3 month', $time);
                    break;
                case 3: // 年卡
                    $expiretime = strtotime('+1 year', $time);
                    break;
                default:
                    $expiretime = $time;
            }
            
            Db::startTrans();
            try {
                // 注册用户
                $result = $this->auth->register($username, $password, $email, $mobile, [
                    'expiretime' => $expiretime
                ]);
                
                if (!$result) {
                    throw new Exception($this->auth->getError());
                }
                
                // 更新卡密状态
                $card->status = 1;
                $card->save();
                
                // 记录卡密使用记录
                CardLog::create([
                    'card_id' => $card->id,
                    'card_no' => $card->card_no,
                    'user_id' => $this->auth->id,
                    'username' => $username,
                    'usetime' => time()
                ]);
                
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            
            $this->success(__('Sign up successful'), url('user/login'));
        }
        return $this->view->fetch();
    }

    // 修改登录方法，增加到期时间验证
    public function login()
    {
        if ($this->auth->id) {
            $this->success(__('You have already logged in'), url('user/index'));
        }
        
        if ($this->request->isPost()) {
            $account = $this->request->post('account');
            $password = $this->request->post('password');
            
            $result = $this->auth->login($account, $password);
            if ($result === true) {
                // 检查账号是否过期
                $user = $this->auth->getUser();
                if ($user && $user->expiretime < time()) {
                    $this->auth->logout();
                    $this->error(__('Account has expired'));
                }
                $this->success(__('Logged in successful'), url('user/index'));
            } else {
                $this->error($this->auth->getError());
            }
        }
        return $this->view->fetch();
    }
} 