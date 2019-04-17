<?php

return [
    // 公共文件
    '__file__' => ['common.php'],

    // api模块
    'api' => [
        '__file__' => ['common.php'],
        '__dir__' => ['model', 'controller'],
    ],

    // admin模块
    'admin' => [
        '__file__' => ['common.php'],
        '__dir__' => ['model', 'view', 'controller'],
    ],
];
