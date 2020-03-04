<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/4
 * Time: 10:05
 * Desc: 常用函数
 */

use App\Services\AppService;
use voku\helper\AntiXSS;
use Monolog\Logger;


/**
 * 语言转换
 * @param string $code
 * @param array $var
 * @return string
 */
function lang(string $code = '', array $var = []): string {
    static $arr;
    if (is_null($arr)) {
        $lang = AppService::getConf('lang');
        $arr  = include DATADIR . "language/{$lang}.php";
    }

    if (empty($code)) {
        return '';
    }

    $msg = $arr[$code] ?? $code;
    if ($msg != $code && !empty($var) && is_array($var)) {
        $keys = array_keys($var);
        $vals = array_values($var);
        array_walk($keys, function (&$item) {
            $item = "{{$item}}";
            return $item;
        });

        $msg = str_replace($keys, $vals, $msg);
    }

    return $msg;
}


/**
 * 获取AntiXSS
 * @return AntiXSS
 */
function getXssObj() {
    static $obj;

    if (is_null($obj)) {
        $obj = new AntiXSS();
    }

    return $obj;
}


/**
 * 获取日志对象
 * @param string $logname
 * @return Logger
 */
function getLogger(string $logname = '') {
    return AppService::getLogger($logname);
}


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
 * 记录异常日志
 * @param string|Throwable $e
 */
function logException($e) {
    if (!empty($e)) {
        $loger = getLogger('exception');
        if(is_object($e) && $e instanceof Throwable) {
            $msg = $e->getMessage() . ' ##code:' . $e->getCode() . ' ##file:' . $e->getFile() . ' ##line:' . $e->getLine();
            $loger->error($msg, $e->getTrace());
        }else{
            $loger->error(strval($e));
        }
    }
}