<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\model\Resource;
use DOMDocument;
use DOMXPath;
use think\Exception;
use think\Log;
use think\Db;

class Collect extends Backend
{
    protected $noNeedLogin = ['api', 'test'];
    protected $noNeedRight = ['*'];
    protected $token = '';  // 添加token属性
    
    // 修改 cookies 配置，从系统配置中读取
    protected static function getCookies()
    {
        return config('site.maopao_cookies') ?: '';
    }
    
    // 添加配置常量
    const TIMEOUT = 30;  // 请求超时时间
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36';
    
    // 修复初始化方法
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new Resource();  // 直接实例化模型
        
        // 从数据库获取配置 - 使用 Db 类直接查询
        $this->token = Db::name('config')
            ->where('name', 'api_token')
            ->value('value');
            
        Log::write('初始化时获取的token值：' . $this->token, 'debug');
        
        if (empty($this->token)) {
            Log::write('api_token 未在数据库中配置或为空', 'error');
        }
        
        $this->view->assign('title', '采集管理');
    }

    // 主页面
    public function index()
    {
        if ($this->request->isAjax()) {
            return $this->crawl();
        }
        return $this->view->fetch();
    }

    /**
     * API接口方法
     */
    public function api()
    {
        // 添加调试日志
        Log::record('API被访问，参数：'.json_encode($this->request->param()), 'info');
        
        // 验证API token
        $token = $this->request->param('token');
        
        // 再次从数据库获取token以确保值是最新的
        $configToken = Db::name('config')
            ->where('name', 'api_token')
            ->value('value');
            
        Log::write('API访问时的token值：请求token=' . $token . '，配置token=' . $configToken, 'debug');
        
        try {
            if (empty($configToken)) {
                return json([
                    'code' => 0,
                    'msg' => 'API token未配置',
                    'data' => null
                ]);
            }

            if (!$token || $token !== $configToken) {
                return json([
                    'code' => 0,
                    'msg' => '无效的token',
                    'debug' => [
                        'request_token' => $token,
                        'config_token' => $configToken
                    ],
                    'data' => null
                ]);
            }

            $limit = $this->request->param('limit/d', 0);
            $result = $this->crawl($limit);
            
            return json([
                'code' => 1,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            Log::record('API调用失败：' . $e->getMessage(), 'error');
            
            return json([
                'code' => 0,
                'msg' => '系统错误',
                'debug' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ],
                'data' => null
            ]);
        }
    }

    // 采集操作移到单独的方法
    public function crawl($limit = 0)
    {
        try {
            // 目标站URL
            $url = "https://www.maomp.net/";
            
            // 记录开始采集的日志
            Log::record('开始采集，目标URL：' . $url, 'info');
            
            // 设置上下文选项，模拟浏览器
            $opts = [
                'http' => [
                    'method' => "GET",
                    'header' => "User-Agent: " . self::USER_AGENT . "\r\n" .
                              "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n" .
                              "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8\r\n" .
                              "Cookie: " . self::getCookies() . "\r\n",
                    'timeout' => self::TIMEOUT,
                ]
            ];
            $context = stream_context_create($opts);
            
            // 获取页面内容
            Log::record('开始获取页面内容', 'info');
            $html = @file_get_contents($url, false, $context);
            
            if ($html === false) {
                $error = error_get_last();
                Log::record('获取页面失败：' . json_encode($error), 'error');
                throw new Exception('无法访问目标站，错误信息：' . ($error['message'] ?? '未知错误'));
            }

            // 检查返回的内容是否为有效的 HTML
            if (empty($html) || strpos($html, '<html') === false) {
                Log::record('返回内容不是有效的 HTML，内容：' . mb_substr($html, 0, 500, 'UTF-8'), 'error');
                throw new Exception('返回内容不是有效的 HTML，无法处理');
            }

            Log::record('成功获取页面，内容长度：' . strlen($html), 'info');
            
            // 创建DOMDocument对象并设置编码
            $dom = new DOMDocument('1.0', 'UTF-8');
            @$dom->loadHTML($html, LIBXML_NOERROR);
            
            // 创建DOMXPath对象
            $xpath = new DOMXPath($dom);
            
            // 查找目标文章列表
            $articles = $xpath->query('//div[@class="cms-news sort" and @name="2"]//article//h2[@class="entry-title"]/a');
            
            if (!$articles || $articles->length == 0) {
                Log::record('未找到文章，HTML片段：' . mb_substr($html, 0, 500, 'UTF-8'), 'error');
                throw new Exception('未找到任何文章');
            }
            
            Log::record('找到文章数量：' . $articles->length, 'info');
            
            $data = [];
            Log::record('开始处理文章列表，共' . $articles->length . '篇文章', 'info');
            
            foreach ($articles as $key => $article) {
                if ($limit > 0 && count($data) >= $limit) {
                    break;
                }
                
                $href = $article->getAttribute('href');
                $title = trim($article->nodeValue);
                
                if (!empty($href) && !empty($title)) {
                    try {
                        // 先检查标题是否已存在 - 由于有唯一索引，这步可以省略，但保留它可以减少不必要的详情请求
                        $exists = $this->model
                            ->where('name', $title)
                            ->find();
                        
                        // 如果已存在，跳过这篇文章
                        if ($exists) {
                            Log::record("文章《{$title}》已存在，跳过", 'info');
                            continue;
                        }

                        $detail = $this->getArticleDetail($href);
                        
                        // 构建数据数组
                        $articleData = [
                            'name' => $title,
                            'content' => $detail['content'],
                            'imgUrl' => $detail['imgUrl'],
                            'diskLink' => $detail['diskLink'],
                            'linkPass' => $detail['linkPass'],
                            'sort' => 1
                        ];
                        
                        // 添加到数据数组中
                        $data[] = $articleData;
                        
                        usleep(500000); // 延迟0.5秒
                        
                    } catch (Exception $e) {
                        Log::record("获取文章 {$href} 详情失败：" . $e->getMessage(), 'error');
                        continue;
                    }
                }
            }
            
            // 批量保存数据
            if (!empty($data)) {
                try {
                    Log::record("开始批量保存数据，共" . count($data) . "条", 'info');
                    $this->model->saveAll($data);
                    Log::record("批量保存数据完成", 'info');
                } catch (\think\exception\PDOException $e) {
                    // 如果是唯一键冲突，记录日志但继续执行
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        Log::record("保存时发现重复数据，已自动跳过", 'info');
                    } else {
                        throw $e;
                    }
                }
            }
            
            return $data;
            
        } catch (Exception $e) {
            Log::record('采集过程发生异常：' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * 获取文章详情
     */
    protected function getArticleDetail($url)
    {
        try {
            // 设置请求头
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Referer: https://www.maomp.net/',
                'sec-ch-ua: "Not(A:Brand";v="99", "Google Chrome";v="133", "Chromium";v="133"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'Upgrade-Insecure-Requests: 1',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36'
            ];

            // 初始化CURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_COOKIE, self::getCookies());
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            // 执行请求
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // 检查请求是否成功
            if ($httpCode !== 200) {
                throw new Exception("获取文章详情失败，HTTP状态码：{$httpCode}，错误信息：{$error}");
            }

            // 解析HTML
            $dom = new DOMDocument('1.0', 'UTF-8');
            @$dom->loadHTML($html, LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);

            // 获取文章内容
            $content = '';
            $baiduUrl = '';
            $linkPass = '';
            $contentNode = $xpath->query('//div[@class="single-content"]')->item(0);
            
            if ($contentNode) {
                // 删除包含特定链接的<a>标签
                $links = $xpath->query('.//a[contains(@href, "https://www.maomp.net/tag/")]', $contentNode);
                foreach ($links as $link) {
                    $link->parentNode->removeChild($link);
                }

                // 删除包含"冒泡网创"的p标签
                $paragraphs = $xpath->query('.//p[contains(text(), "冒泡网创")]', $contentNode);
                foreach ($paragraphs as $p) {
                    $p->parentNode->removeChild($p);
                }

                // 处理图片路径
                $images = $xpath->query('.//img', $contentNode);
                foreach ($images as $img) {
                    $src = $img->getAttribute('src');
                    if (strpos($src, 'http') !== 0) {
                        // 如果是相对路径，转换为绝对路径
                        if (strpos($src, '/') === 0) {
                            $img->setAttribute('src', 'https://www.maomp.net' . $src);
                        } else {
                            $img->setAttribute('src', 'https://www.maomp.net/' . $src);
                        }
                    }
                }

                // 先提取百度网盘链接和密码
                $baiduUrl = '';
                $linkPass = '';
                
                // 查找包含百度网盘链接的文本
                $text = $dom->saveHTML($contentNode);
                if (preg_match('/链接：(https?:\/\/pan\.baidu\.com\/[^\s<]+)/', $text, $matches) 
                    || preg_match('/(https?:\/\/pan\.baidu\.com\/[^\s<]+)/', $text, $matches)) {
                    $baiduUrl = $matches[1];
                }
                
                // 提取密码
                if (preg_match('/密码[：:]\s*([a-zA-Z0-9]{4})/', $text, $matches)) {
                    $linkPass = $matches[1];
                }

                // 找到最后一个 strong 标签
                $strongs = $xpath->query('.//strong', $contentNode);
                if ($strongs->length > 0) {
                    $lastStrong = $strongs->item($strongs->length - 1);
                    
                    // 删除最后一个 strong 标签及其后面的所有内容
                    $currentNode = $lastStrong;
                    while ($currentNode) {
                        $nodeToRemove = $currentNode;
                        $currentNode = $currentNode->nextSibling;
                        $nodeToRemove->parentNode->removeChild($nodeToRemove);
                    }
                }
                
                // 找到并删除 favorite-box
                $favoriteBoxes = $xpath->query('.//div[@class="favorite-box"]', $contentNode);
                foreach ($favoriteBoxes as $favoriteBox) {
                    $favoriteBox->parentNode->removeChild($favoriteBox);
                }
                
                // 保存处理后的内容
                $content = $dom->saveHTML($contentNode);
                
                // 清理特定字符串和样式
                $content = str_replace('【更多资源www.maomp.fun】', '', $content);
                $content = str_replace('【更多资源www.maomp.net】', '', $content);
                $content = str_replace('【更多资源www.maomp.com】', '', $content);
                $content = preg_replace('/\s+/', ' ', $content);
                $content = str_replace('　', '', $content);
                $content = preg_replace('/<p>\s*<\/p>/', '', $content);
                $content = preg_replace('/【更多资源www\.maomp\.[a-z]+】/i', '', $content);
                $content = preg_replace('/<a[^>]*href="[^"]*maomp\.net\/tag\/[^"]*"[^>]*>.*?<\/a>/i', '', $content);
                $content = preg_replace('/<p[^>]*>[^<]*冒泡网创[^<]*<\/p>/i', '', $content);
                
                // 从内容中移除百度网盘链接和密码
                $content = preg_replace('/链接：https?:\/\/pan\.baidu\.com\/[^\s<]+/', '', $content);
                $content = preg_replace('/密码[：:]\s*[a-zA-Z0-9]{4}/', '', $content);
                
                // 清理可能残留的空段落
                $content = preg_replace('/<p>\s*<\/p>/', '', $content);
            }

            // 获取第一张图片URL（现在已经是绝对路径了）
            $imgUrl = '';
            $images = $xpath->query('//div[@class="single-content"]//img');
            if ($images->length > 0) {
                $imgUrl = $images->item(0)->getAttribute('src');
            }

            // 添加内容验证
            if (empty($content)) {
                throw new Exception('文章内容为空');
            }

            // 确保返回数据的完整性
            return [
                'content' => $content ?: '',
                'diskLink' => $baiduUrl ?: '',  // 保存提取到的网盘链接
                'linkPass' => $linkPass ?: '',
                'imgUrl' => $imgUrl ?: ''
            ];
            
        } catch (Exception $e) {
            Log::record('获取文章详情失败：' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * 测试API是否可访问
     */
    public function test()
    {
        $this->success('API可以访问', null, [
            'url' => $this->request->url(true),
            'module' => $this->request->module(),
            'controller' => $this->request->controller(),
            'action' => $this->request->action()
        ]);
    }

    /**
     * 配置管理
     */
    public function config()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $config = get_addon_config('');
                $config = array_merge($config, $params);
                set_addon_config('', $config);
                $this->success();
            }
            $this->error();
        }
        $this->view->assign("options", get_addon_config(''));
        return $this->view->fetch();
    }
}