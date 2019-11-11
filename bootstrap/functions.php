<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2019/11/10
 * Time: 13:56
 * Desc: 常用函数
 */

use App\Services\AppService;
use Monolog\Logger;

/**
 * 获取异常信息
 * @param Throwable $e
 * @return string
 */
function getExceptionMsg(Throwable $e) {
    $msg = $e->getMessage() . ' ##code:' . $e->getCode() . ' ##file:' . $e->getFile() . ' ##line:' . $e->getLine();
    return $msg;
}


/**
 * 获取日志对象
 * @param string|null $logname
 * @return Logger
 */
function getLogger(string $logname = null) {
    return AppService::getLogger($logname);
}