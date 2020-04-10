<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/4
 * Time: 10:03
 * Desc: 常量定义
 */

define('DS', str_replace('\\', '/', DIRECTORY_SEPARATOR));
define('PS', PATH_SEPARATOR);

define('ROOTDIR', str_replace('\\', '/', dirname(__DIR__)) . DS ); //根目录
define('WWWDIR',    ROOTDIR .'public'       . DS ); //WEB入口目录
define('BOOTDIR',   ROOTDIR .'bootstrap'    . DS );
define('APPSDIR',   ROOTDIR .'app'          . DS );
define('CONFDIR',   ROOTDIR .'config'       . DS );
define('DATADIR',   ROOTDIR .'data'         . DS );
define('RUNTDIR',   ROOTDIR .'runtime'      . DS );
define('VIEWDIR',   APPSDIR .'Views'        . DS ); //视图目录
define('LOGDIR',    RUNTDIR .'logs'         . DS );

define('APP_NAME', 'MiniApp');
define('APP_VERSION', '0.0.7');
define('DEBUG_OPEN', true); //是否开启调试