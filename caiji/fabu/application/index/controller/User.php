<?php
namespace app\index\controller;

use app\common\controller\Frontend;

class User extends Frontend
{
    protected $layout = 'default';
    protected $noNeedLogin = ['login', 'register', 'third'];
    
    public function released_config()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            
            // 记录请求参数
            \think\Log::write('Released config request params: ' . json_encode($params, JSON_UNESCAPED_UNICODE), 'info');
            
            // 构建要保存的配置数据结构
            $configData = [
                'theme_settings' => [
                    'theme' => $params['theme'],
                    'publish_key' => $params['publish_key']
                ],
                'price_settings' => [
                    'pay_mode' => $params['pay_mode'],
                    'normal_price' => $params['normal_price'],
                    'vip_price' => $params['vip_price'],
                    'svip_price' => $params['svip_price']
                ],
                'category_mapping' => [
                    'zhongchuang' => $params['zhongchuang'],
                    'maopao' => $params['maopao'],
                    'fuyuan' => $params['fuyuan']
                ]
            ];
            
            try {
                // 查找是否已存在配置
                $existConfig = \think\Db::name('user_released')
                    ->where('user_id', $this->auth->id)
                    ->find();
                    
                $data = [
                    'user_id' => $this->auth->id,
                    'released_config' => json_encode($configData, JSON_UNESCAPED_UNICODE)  // 将配置数据转为JSON存储
                ];
                
                // 记录即将保存的数据
                \think\Log::write('Released config saving data: ' . json_encode($data, JSON_UNESCAPED_UNICODE), 'info');
                
                // 更新或插入数据
                if ($existConfig) {
                    $result = \think\Db::name('user_released')
                        ->where('user_id', $this->auth->id)
                        ->update($data);
                    \think\Log::write('Released config updated for user_id: ' . $this->auth->id, 'info');
                } else {
                    $result = \think\Db::name('user_released')->insert($data);
                    \think\Log::write('Released config inserted for user_id: ' . $this->auth->id, 'info');
                }
                
                if ($result !== false) {
                    \think\Log::write('Released config saved successfully', 'info');
                    $this->success('保存成功', null, $configData);
                } else {
                    \think\Log::write('Released config save failed: no rows affected', 'error');
                    $this->error('保存失败');
                }
            } catch (\Exception $e) {
                \think\Log::write('Released config save exception: ' . $e->getMessage(), 'error');
                $this->error('系统错误：' . $e->getMessage());
            }
            return;
        }
        
        // 获取已有配置
        try {
            $config = \think\Db::name('user_released')
                ->where('user_id', $this->auth->id)
                ->find();
                
            if ($config) {
                $configData = json_decode($config['released_config'], true);
                \think\Log::write('Released config loaded for user_id: ' . $this->auth->id, 'info');
                $this->view->assign('config', $configData);
            } else {
                \think\Log::write('No released config found for user_id: ' . $this->auth->id, 'info');
            }
        } catch (\Exception $e) {
            \think\Log::write('Released config load exception: ' . $e->getMessage(), 'error');
        }
        
        return $this->view->fetch();
    }
} 