<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2019/11/10
 * Time: 13:48
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

define('DEBUG_OPEN', false); //是否开启调试