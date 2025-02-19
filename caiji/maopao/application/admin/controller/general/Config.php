<?php

namespace app\admin\controller\general;

use app\admin\controller\Backend;

class Config extends Backend
{
    private function _form_fields()
    {
        // ... existing fields ...
        
        $fields = [
            // ... other fields ...
            
            [
                'name'    => 'maopao_cookies',
                'title'   => '冒泡网站Cookies',
                'type'    => 'text',
                'content' => [],
                'tip'     => '采集程序使用的Cookies',
                'rule'    => '',
                'extend'  => '',
                'setting' => '',
                'group'   => 'basic'
            ],
            
            // ... other fields ...
        ];
        
        return $fields;
    }
} 