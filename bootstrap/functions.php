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


/**
 * 语言转换
 * @param string $code
 * @param array $var
 * @return mixed|string
 */
function lang($code='', array $var=[]) {
    static $arr;
    if(is_null($arr)) {
        $lang = AppService::getConf('lang');
        $arr = include DATADIR. "language/{$lang}.php";
    }

    if(empty($code)) {
        return '';
    }

    $msg = $arr[$code] ?? $code;
    if($msg != $code && !empty($var) && is_array($var)) {
        $keys = array_keys($var);
        $vals = array_values($var);
        array_walk($keys, function(&$item) {
            $item = "{{$item}}";
            return $item;
        });

        $msg = str_replace($keys, $vals, $msg);
    }

    return $msg;
}