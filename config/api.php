<?php
/**
 * Created by PhpStorm.
 * User: 70473
 * Date: 2018/10/9
 * Time: 11:12
 */

return [
    'jh' => [
        'center' => 'http://user.zjut.com/api.php',
        'template' => 'https://server.wejh.imcr.me/api/notification/walk',
        'oauth' => 'https://craim.net/oauth/index.php?url='
    ],
    //TODO: 使用其他方式解决不能动态修改设置的问题

    'system' => [
        'BeginTime'=>env('BeginTime'),
        'EndTime' => env('EndTime'),
        'IsEnd' => false
    ]
];
