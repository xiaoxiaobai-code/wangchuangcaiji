<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Validate;
use think\Db;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'register', 'resetpwd', 'changeemail', 'changemobile'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 会员登录
     */
    public function login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            // 验证账号是否过期
            $user = $this->auth->getUser();
            if ($user['expire_time'] && $user['expire_time'] < time()) {
                $this->auth->logout();
                $this->error('账号已过期，请联系管理员');
            }
            
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     */
    public function register()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            
            // 验证token
            $rule = [
                'username'   => 'require|length:3,30',
                'password'   => 'require|length:6,30',
                'email'      => 'require|email',
                'mobile'     => 'require|mobile',
                'card_no'    => 'require'
            ];

            $msg = [
                'username.require' => '请输入用户名',
                'username.length'  => '用户名长度必须在3-30个字符之间',
                'password.require' => '请输入密码',
                'password.length'  => '密码长度必须在6-30个字符之间',
                'email.require'    => '请输入邮箱',
                'email.email'      => '邮箱格式不正确',
                'mobile.require'   => '请输入手机号',
                'mobile.mobile'    => '手机号格式不正确',
                'card_no.require'  => '请输入注册卡密'
            ];

            $validate = new Validate($rule, $msg);
            if (!$validate->check($params)) {
                $this->error($validate->getError());
            }

            // 验证卡密
            $card = model('Card')->where(['card_no' => $params['card_no'], 'status' => 0])->find();
            if (!$card) {
                $this->error('卡密无效或已被使用');
            }

            Db::startTrans();
            try {
                // 计算过期时间
                $expire_time = time();
                switch ($card['type']) {
                    case 1: // 月卡
                        $expire_time += 30 * 86400;
                        break;
                    case 2: // 季卡
                        $expire_time += 90 * 86400;
                        break;
                    case 3: // 年卡
                        $expire_time += 365 * 86400;
                        break;
                }

                // 注册用户
                $result = $this->auth->register(
                    $params['username'],
                    $params['password'],
                    $params['email'],
                    $params['mobile'],
                    ['expire_time' => $expire_time]
                );

                if (!$result) {
                    throw new \Exception($this->auth->getError());
                }

                $user = $this->auth->getUser();

                // 更新卡密状态
                $card->save([
                    'status' => 1,
                    'use_time' => time(),
                    'user_id' => $user->id
                ]);

                // 自动登录用户
                $this->auth->direct($user->id);

                Db::commit();
                $this->success('注册成功', [
                    'userinfo' => $this->auth->getUserinfo(),
                    'url' => url('user/index')  // 添加跳转URL
                ]);
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        $this->error("非法请求");
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        if ($this->request->isPost()) {
            $this->auth->logout();
            $this->success(__('Logout successful'));
        }
        $this->error(__('Invalid parameters'));
    }

    /**
     * 修改会员个人信息
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->post('username');
        $nickname = $this->request->post('nickname');
        $bio = $this->request->post('bio');
        $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
        
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)
                ->where('id', '<>', $this->auth->id)
                ->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)
                ->where('id', '<>', $this->auth->id)
                ->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }
        
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        
        $this->success();
    }

    /**
     * 修改密码
     */
    public function changepwd()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        
        $oldpassword = $this->request->post("oldpassword");
        $newpassword = $this->request->post("newpassword");
        
        if (!$newpassword || !$oldpassword) {
            $this->error(__('Invalid parameters'));
        }
        
        $ret = $this->auth->changepwd($newpassword, $oldpassword);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }
} 