<?php
use think\Route;

// 添加卡密相关路由
Route::group('card', function() {
    Route::get('generate', 'card/generate');
    Route::post('generate', 'card/generate');
    Route::get('log', 'card/log');
}); 