<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'task_queue' => [
            'class'    =>'common\components\TaskQueue\Queue',
            'hostname' => getenv('YII_REDIS_ALL_HOST'),
            'port'     => getenv('YII_REDIS_ALL_PORT'),
            'password' => getenv('YII_REDIS_ALL_PASS'),
            'database' => getenv('YII_REDIS_QUEUE_DBINDEX'),
        ],        
    ],
];
