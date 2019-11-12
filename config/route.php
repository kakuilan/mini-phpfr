<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2019/11/12
 * Time: 15:05
 * Desc: 路由配置
 */

return [
    [
        'method'=>'GET', //请求方法
        'path'=>'/test', //匹配路径
        'handler'=>'Test@index', //处理器,形如"Controller@action",控制器需首字母大写,动作为小写.
    ],
    [
        'method'=>'POST',
        'path'=>'/test/post',
        'handler'=>'Test@post',
    ],
];