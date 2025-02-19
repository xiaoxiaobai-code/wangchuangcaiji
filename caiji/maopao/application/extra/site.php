<?php

return [
    'name' => '我的网站',
    'beian' => '',
    'cdnurl' => '',
    'version' => '1.0.1',
    'timezone' => 'Asia/Shanghai',
    'forbiddenip' => '',
    'languages' => [
        'backend' => 'zh-cn',
        'frontend' => 'zh-cn',
    ],
    'fixedpage' => 'dashboard',
    'categorytype' => [
        'default' => 'Default',
        'page' => 'Page',
        'article' => 'Article',
        'test' => 'Test',
    ],
    'configgroup' => [
        'basic' => 'Basic',
        'email' => 'Email',
        'dictionary' => 'Dictionary',
        'user' => 'User',
        'example' => 'Example',
    ],
    'attachmentcategory' => [
        'category1' => 'Category1',
        'category2' => 'Category2',
        'custom' => 'Custom',
    ],
    'mail_type' => '1',
    'mail_smtp_host' => 'smtp.qq.com',
    'mail_smtp_port' => '465',
    'mail_smtp_user' => '',
    'mail_smtp_pass' => '',
    'mail_verify_type' => '2',
    'mail_from' => '',
    
    // 添加API token配置
    'api_token' => 'qq872672419',  // 确保这里的token值与您的请求中使用的token一致
    
    // 添加冒泡网站cookies配置
    'maopao_cookies' => '',
]; 