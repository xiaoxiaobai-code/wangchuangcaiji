<?php

// 数据库配置
$config = [
    'host' => 'localhost',
    'dbname' => 'fastadmin',
    'username' => 'root',
    'password' => 'root'
];

// 添加处理单个资源的函数
function getCategoryBySort($sort,$releasedConfig) {
    switch ($sort) {
        case 1:
            return '4';  // 中创
        case 2:
            return '5';  // 小说
        case 3:
            return '6';  // 漫画
        case 4:
            return '7';  // 音乐
        case 5:
            return '8';  // 影视
        default:
            return '4';  // 默认分类
    }
}

function processResource($resource, $releasedConfig) {
    // 添加调试信息
    echo "开始请求了.................... \n";
	echo '请求获取的分类'.getCategoryBySort($resource['sort']);
    
    // 检查releasedConfig是否已经是数组
    if (is_array($releasedConfig)) {
        $configData = $releasedConfig;
    } else {
        // 如果不是数组，尝试JSON解析
        $configData = json_decode($releasedConfig, true);
    }
    
    echo "配置数据类型: " . gettype($releasedConfig) . "\n";
    echo "处理后的配置数据: \n";
    print_r($configData);
    
    // 配置检查
    if (!isset($configData['theme_settings'])) {
        echo "配置数据不完整 - 缺少theme_settings：\n";
        print_r($configData);
        throw new Exception('配置数据不完整 - 缺少theme_settings');
    }
    
    if (!isset($configData['theme_settings']['website'])) {
        echo "配置数据不完整 - 缺少website：\n";
        print_r($configData['theme_settings']);
        throw new Exception('配置数据不完整 - 缺少website');
    }
    
    if (!isset($configData['theme_settings']['publish_key'])) {
        echo "配置数据不完整 - 缺少publish_key：\n";
        print_r($configData['theme_settings']);
        throw new Exception('配置数据不完整 - 缺少publish_key');
    }
    
    // 准备POST数据
    $postData = [
        'post_title' => $resource['name'],
        'content' => sprintf(
            "<p></p><p><img alt=\"%s\" class=\"aligncenter wp-image-109808\" src=\"%s\"/></p><p>%s</p>[hidecontent type=payshow]%s[/hidecontent]",
            $resource['imgUrl'],
            $resource['imgUrl'],
            $resource['content'],
            $resource['diskLink'] . ($resource['linkPass'] ? ' 提取码：' . $resource['linkPass'] : '')
        ),
        'post_category' => getCategoryBySort($resource['sort'],$releasedConfig),
        'post_topic' => 'TopicID',
        'topic_name' => 'category',
        'post_date' => '',
        'zibi_posts_zibpay' => [
            'pay_type' => '1',
            'pay_price' => $configData['price_settings']['normal_price'] ?? '9.9',
            'pay_original_price' => '99',
            'vip_1_price' => $configData['price_settings']['vip_price'] ?? '0',
            'vip_2_price' => $configData['price_settings']['svip_price'] ?? '0',
            'pay_download' => [
                ['link' => '', 'more' => ''],
                ['link' => '', 'more' => ''],
                ['link' => '', 'more' => '']
            ],
            'pay_cuont' => '',
            'pay_title' => '',
            'pay_doc' => '',
            'pay_extra_hide' => '',
            'pay_details' => '',
            'pay_rebate_discount' => '',
            'attributes' => [
                ['key' => '', 'value' => ''],
                ['key' => '', 'value' => '']
            ],
            'demo_link' => [
                ['url' => '']
            ],
            'pay_modo' => '',
            'points_price' => '',
            'vip_1_points' => '',
            'vip_2_points' => '',
            'pay_limit' => '0'
        ]
    ];

    // 创建CURL请求
    $ch = curl_init();
    $url = $configData['theme_settings']['website'] . '/?xbzyk_plugin_post=null&private=' . $configData['theme_settings']['publish_key'];
    
    // 添加更多调试信息
    echo "请求URL: " . $url . "\n";
    echo "POST数据: " . json_encode($postData, JSON_UNESCAPED_UNICODE) . "\n";

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // 在processResource函数中的curl设置部分添加
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    // 存储verbose信息供后续使用
    curl_setopt($ch, CURLOPT_PRIVATE, $verbose);
    
    return $ch;
}

function sendRequest($resource, $configData) {
    echo "开始发送请求...\n";
    echo "资源ID: " . $resource['id'] . "\n";
    
    // 准备POST数据
    $postData = [
        'post_title' => $resource['name'],
        'content' => sprintf(
            "<p></p><p><img alt=\"%s\" class=\"aligncenter wp-image-109808\" src=\"%s\"/></p><p>%s</p>[hidecontent type=payshow]%s[/hidecontent]",
            $resource['imgUrl'],
            $resource['imgUrl'],
            $resource['content'],
            $resource['diskLink'] . ($resource['linkPass'] ? ' 提取码：' . $resource['linkPass'] : '')
        ),
        'post_category' => getCategoryBySort($resource['sort']),
        'post_topic' => 'TopicID',
        'topic_name' => 'category',
        'post_date' => '',
        'zibi_posts_zibpay' => [
            'pay_type' => '1',
            'pay_price' => $configData['price_settings']['normal_price'] ?? '9.9',
            'pay_original_price' => '99',
            'vip_1_price' => $configData['price_settings']['vip_price'] ?? '0',
            'vip_2_price' => $configData['price_settings']['svip_price'] ?? '0',
            'pay_download' => [
                ['link' => '', 'more' => ''],
                ['link' => '', 'more' => ''],
                ['link' => '', 'more' => '']
            ],
            'pay_cuont' => '',
            'pay_title' => '',
            'pay_doc' => '',
            'pay_extra_hide' => '',
            'pay_details' => '',
            'pay_rebate_discount' => '',
            'attributes' => [
                ['key' => '', 'value' => ''],
                ['key' => '', 'value' => '']
            ],
            'demo_link' => [
                ['url' => '']
            ],
            'pay_modo' => '',
            'points_price' => '',
            'vip_1_points' => '',
            'vip_2_points' => '',
            'pay_limit' => '0'
        ]
    ];

    $url = $configData['theme_settings']['website'] . '/?xbzyk_plugin_post=null&private=' . $configData['theme_settings']['publish_key'];
    
    echo "请求URL: " . $url . "\n";
    echo "POST数据: " . json_encode($postData, JSON_UNESCAPED_UNICODE) . "\n";

    // 创建curl请求
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);

    // 执行请求
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($error) {
        echo "CURL错误: " . $error . "\n";
    }

    echo "HTTP状态码: " . $httpCode . "\n";
    echo "响应内容: \n";
    print_r($response);
    echo "\n";

    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

try {
    // 连接数据库
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
        $config['username'],
        $config['password']
    );
    
    // 设置PDO错误模式为异常
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 获取当前时间戳
    $currentTimestamp = time();
    
    // 添加调试信息
    //echo "当前时间戳: " . $currentTimestamp . "\n";
    
    // 查询fa_user表中expire_time大于当前时间戳的记录
    $stmt = $pdo->prepare("SELECT id, expire_time FROM fa_user WHERE expire_time > :current_time");
    $stmt->execute(['current_time' => $currentTimestamp]);
    $validUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 添加调试信息
    //echo "查询到的用户数据: \n";
    //print_r($validUsers);
    
    if (empty($validUsers)) {
        echo json_encode([
            'status' => 'error',
            'message' => '没有找到符合条件的用户'
        ]);
        exit;
    }
    
    // 获取released_config数据
    $validUserIds = array_column($validUsers, 'id');
    $placeholders = str_repeat('?,', count($validUserIds) - 1) . '?';
    $query = "SELECT released_config FROM fa_user_released WHERE user_id IN ($placeholders)";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($validUserIds);
    $userReleasedData = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 在获取released_config数据后添加调试信息
    //echo "获取到的released_config数据：\n";
    //print_r($userReleasedData);
    
    // 解码JSON
    $processedReleasedData = array_map(function($config) {
        echo "正在处理的原始配置数据：\n";
        print_r($config);
        
        $decoded = json_decode($config, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "JSON解析错误：" . json_last_error_msg() . "\n";
            echo "原始数据：" . $config . "\n";
            return null;
        }
        
        echo "解码后的配置数据：\n";
        print_r($decoded);
        
        // 检查必要的字段
        if (!isset($decoded['theme_settings'])) {
            echo "缺少 theme_settings\n";
        } else {
            if (!isset($decoded['theme_settings']['website'])) {
                echo "缺少 theme_settings.website\n";
            }
            if (!isset($decoded['theme_settings']['publish_key'])) {
                echo "缺少 theme_settings.publish_key\n";
            }
        }
        
        return $decoded;
    }, $userReleasedData);
    
    // 过滤掉无效的配置
    $processedReleasedData = array_filter($processedReleasedData, function($config) {
        return $config !== null && 
               isset($config['theme_settings']) && 
               isset($config['theme_settings']['website']) && 
               isset($config['theme_settings']['publish_key']);
    });
    
    echo "有效的配置数量: " . count($processedReleasedData) . "\n";
    echo "有效的配置数据：\n";
    print_r($processedReleasedData);
    
    if (empty($processedReleasedData)) {
        echo "错误：没有有效的配置数据\n";
        exit;
    }
    
    // 查询resource表最新200条数据
    $resourceQuery = "SELECT 
        id,
        name,
        content,
        imgUrl,
        diskLink,
        linkPass,
        sort,
        updateTime,
        createTime
    FROM resource 
    ORDER BY updateTime DESC 
    LIMIT 200";
    
    // 添加调试信息
    echo "执行的资源查询SQL: " . $resourceQuery . "\n";
    
    try {
        $stmt = $pdo->prepare($resourceQuery);
        $stmt->execute();
        $resourceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 检查SQL错误
        if ($stmt->errorCode() !== '00000') {
            $error = $stmt->errorInfo();
            echo "SQL错误: " . print_r($error, true) . "\n";
        }
        
        // 立即输出结果
        echo "SQL执行完成\n";
        echo "获取到的资源数据数量: " . count($resourceData) . "\n";
        
        if (empty($resourceData)) {
            echo "警告：没有找到任何资源数据\n";
            // 检查表是否存在
            $checkTable = $pdo->query("SHOW TABLES LIKE 'resource'");
            if ($checkTable->rowCount() == 0) {
                echo "错误：resource表不存在!\n";
            } else {
                // 检查表中是否有数据
                $countCheck = $pdo->query("SELECT COUNT(*) FROM resource");
                $totalCount = $countCheck->fetchColumn();
                echo "resource表中总共有 {$totalCount} 条记录\n";
            }
        } else {
            echo "第一条资源数据示例:\n";
            print_r($resourceData[0]);
        }
        
    } catch (PDOException $e) {
        echo "查询resource表时出错: " . $e->getMessage() . "\n";
        // 检查表是否存在
        try {
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "数据库中的表:\n";
            print_r($tables);
        } catch (Exception $e2) {
            echo "无法列出数据库表: " . $e2->getMessage() . "\n";
        }
    }
    
    echo "处理的released_config数量: " . count($processedReleasedData) . "\n";
    
    // 循环处理每个资源
    $results = [];
    foreach ($resourceData as $resource) {
        foreach ($processedReleasedData as $config) {
            echo "\n=== 处理资源 ID: {$resource['id']} ===\n";
            
            try {
                $result = sendRequest($resource, $config);
                $results[] = [
                    'resource_id' => $resource['id'],
                    'result' => $result
                ];
                
                // 可以在这里添加延时，避免请求太快
                sleep(1);
                
            } catch (Exception $e) {
                echo "处理资源时出错: " . $e->getMessage() . "\n";
                continue;
            }
        }
    }

    // 输出所有结果
    echo "\n=== 所有请求完成，结果如下 ===\n";
    foreach ($results as $result) {
        echo "\n资源ID: " . $result['resource_id'] . "\n";
        echo "HTTP状态码: " . $result['result']['http_code'] . "\n";
        echo "错误信息: " . ($result['result']['error'] ?: "无") . "\n";
        echo "响应内容:\n";
        print_r($result['result']['response']);
        echo "\n";
    }

} catch (PDOException $e) {
    // 处理数据库错误
    echo json_encode([
        'status' => 'error',
        'message' => '数据库错误：' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // 处理其他错误
    echo json_encode([
        'status' => 'error',
        'message' => '系统错误：' . $e->getMessage()
    ]);
}
?>  