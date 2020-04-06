<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2020/4/6
 * Time: 12:34
 * Desc: cli入口文件
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap/define.php';
require __DIR__ . '/../bootstrap/functions.php';

date_default_timezone_set('Asia/Shanghai');
mb_substitute_character('none');

//开启调试
if (DEBUG_OPEN) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

$logFile = LOGDIR . '/phperr_' . date('Ymd') . '.log';
register_shutdown_function('\Kph\Helpers\DebugHelper::errorLogHandler', $logFile);

// 运行app
App\Services\AppService::setConfDir(CONFDIR);
App\Services\AppService::runCliApp();