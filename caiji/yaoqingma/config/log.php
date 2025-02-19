return [
    'default'      => 'file',
    'channels'     => [
        'file' => [
            'type'  => 'file',
            'path'  => '../runtime/log/',
            'level' => ['notice', 'error', 'info'],
        ],
    ],
]; 