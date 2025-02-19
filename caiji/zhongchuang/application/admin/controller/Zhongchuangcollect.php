<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Log;
use think\Config;
use think\Db;

class Zhongchuangcollect extends Backend
{
    protected $noNeedLogin = ['collect'];  // 无需登录即可访问的方法
    protected $noNeedRight = ['collect'];  // 无需鉴权的方法
    protected $targetUrl = '';
    protected $token = '';
    protected $cookie = '';
    
    public function _initialize()
    {
        parent::_initialize();
        
        // 从数据库获取配置
        $config = \app\common\model\Config::where('name', 'zhongchuang_token')->value('value');
        Log::write('数据库配置值：' . $config, 'debug');
        
        $this->token = $config ?: '';
        if (empty($this->token)) {
            Log::write('zhongchuang_token 未在数据库中配置或为空', 'error');
        }
        
        $this->targetUrl = \app\common\model\Config::where('name', 'zhongchuang_url')->value('value') ?: 'https://www.you85.net/';
        $this->cookie = \app\common\model\Config::where('name', 'zhongchuang_cookie')->value('value') ?: '';
    }
    
    /**
     * 采集接口
     */
    public function collect()
    {
        // 在验证token前添加日志
        Log::write('请求token: ' . $this->request->header('token'), 'debug');
        Log::write('系统token: ' . $this->token, 'debug');
        
        // 验证token
        $token = $this->request->header('token') ?: $this->request->request('token');
        if (!$token) {
            return json(['code' => 0, 'msg' => 'Token不能为空', 'data' => []]);
        }
        if ($token !== $this->token) {
            return json(['code' => 0, 'msg' => 'Token验证失败：请求token=' . $token . '，系统token=' . $this->token, 'data' => []]);
        }

        try {
            $headers = [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Cookie: ' . $this->cookie
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->targetUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $html = curl_exec($ch);
            
            if (curl_errno($ch)) {
                return json(['code' => 0, 'msg' => 'CURL错误: ' . curl_error($ch), 'data' => []]);
            }
            
            curl_close($ch);
            
            // GBK转UTF-8
            $html = iconv('GBK', 'UTF-8//IGNORE', $html);
            
            if (empty($html)) {
                return json(['code' => 0, 'msg' => '获取到的页面内容为空', 'data' => []]);
            }

            // 使用正则表达式直接匹配所需数据
            $data = [];
            $pattern = '/<div[^>]*class="pic"[^>]*>.*?<a[^>]*href="([^"]*)"[^>]*>.*?<img[^>]*src="([^"]*)"[^>]*alt="([^"]*)".*?<\/a>/is';
            
            if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $key => $match) {
                    if ($key >= 10) break;  // 只取前10条
                    
                    $href = $this->formatUrl(trim($match[1]));
                    
                    // 获取文章内容
                    $content = $this->getContent($href);
                    
                    // 准备数据
                    $insertData = [
                        'name' => trim($match[3]),
                        'content' => $content['content'],
                        'imgUrl' => $this->formatUrl(trim($match[2])),
                        'diskLink' => $content['baiduUrl'],
                        'linkPass' => $content['baiduCode'],
                        'sort' => 2,
                        'updateTime' => date('Y-m-d H:i:s'),
                        'createTime' => date('Y-m-d H:i:s')
                    ];
                    
                    try {
                        // 使用 Db::table() 方法指定完整表名，而不是 Db::name()
                        $id = Db::table('resource')->insertGetId($insertData);
                        if ($id) {
                            $insertData['id'] = $id;
                            $data[] = $insertData;
                        }
                    } catch (\think\Exception $e) {
                        // 如果是唯一键冲突，则跳过
                        if (stripos($e->getMessage(), 'Duplicate entry') !== false) {
                            continue;
                        }
                        // 其他错误则抛出
                        throw $e;
                    }
                }
            }
            
            if (empty($data)) {
                // 输出一部分HTML内容以便调试
                $preview = substr($html, 0, 1000);
                return json(['code' => 0, 'msg' => '未找到文章数据', 'data' => ['html_preview' => $preview]]);
            }
            
            return json(['code' => 1, 'msg' => '采集成功，成功保存' . count($data) . '条记录', 'data' => $data]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '采集失败：' . $e->getMessage(), 'data' => []]);
        }
    }
    
    /**
     * 格式化URL
     * @param string $url
     * @return string
     */
    protected function formatUrl($url)
    {
        if (strpos($url, 'http') !== 0) {
            $url = rtrim($this->targetUrl, '/') . '/' . ltrim($url, '/');
        }
        return $url;
    }

    /**
     * 采集文章内容
     * @param string $url 文章URL
     * @return string
     */
    protected function getContent($url)
    {
        $result = [
            'content' => '',
            'baiduUrl' => '',
            'baiduCode' => ''
        ];
        
        // 获取文章内容
        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Cookie: ' . $this->cookie
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $html = curl_exec($ch);
        curl_close($ch);
        
        // GBK转UTF-8
        $html = iconv('GBK', 'UTF-8//IGNORE', $html);
        
        // 匹配项目介绍部分，直接获取原始内容
        if (preg_match('/<font size="4">(.*?)(?:给力项目|友情提醒)/is', $html, $matches)) {
            // 直接使用匹配到的内容，保持原有格式
            $result['content'] = $matches[1];
        }
        
        // 从URL中提取tid
        if (preg_match('/thread-(\d+)-/i', $url, $matches)) {
            $tid = $matches[1];
            
            // 获取下载页内容
            $downloadUrl = "https://www.you85.net/plugin.php?id=threed_sorrt:downld&tid={$tid}&infloat=yes&handlekey=downld&inajax=1&ajaxtarget=fwin_content_downld";
            
            $headers = [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',
                'Accept: */*',
                'Accept-Language: zh-CN,zh;q=0.9',
                'X-Requested-With: XMLHttpRequest',
                'Referer: ' . $url,
                'Cookie: ' . $this->cookie
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $downloadUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $downloadHtml = curl_exec($ch);
            curl_close($ch);
            
            // GBK转UTF-8
            $downloadHtml = iconv('GBK', 'UTF-8//IGNORE', $downloadHtml);
            
            // 匹配百度网盘链接和提取码
            if (preg_match('/href="(https?:\/\/pan\.baidu\.com\/s\/[^"]+)"[^>]*>.*?提取码:([^<]*)/is', $downloadHtml, $matches)) {
                $result['baiduUrl'] = $matches[1];
                // 如果链接中包含提取码，从链接中提取
                if (preg_match('/\?pwd=(\w+)/', $matches[1], $pwdMatches)) {
                    $result['baiduCode'] = $pwdMatches[1];
                } else {
                    // 否则使用提取码字段
                    $result['baiduCode'] = trim($matches[2]);
                }
            }
        }
        
        return $result;
    }
} 