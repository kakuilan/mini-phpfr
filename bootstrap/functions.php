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
use Medoo\Medoo;

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
function getXssObj(): AntiXSS {
    static $obj;

    if (is_null($obj)) {
        $obj = new AntiXSS();
    }

    return $obj;
}


/**
 * 获取日志对象
 * @param string $logName
 * @return Logger
 */
function getLogger(string $logName = ''): Logger {
    return AppService::getLogger($logName);
}


/**
 * 获取异常信息
 * @param Throwable $e
 * @return string
 */
function getExceptionMsg(Throwable $e): string {
    return $e->getMessage() . ' ##code:' . $e->getCode() . ' ##file:' . $e->getFile() . ' ##line:' . $e->getLine();
}


/**
 * 记录异常日志
 * @param string|Throwable $e
 */
function logException($e) {
    if (!empty($e)) {
        $logger = getLogger('exception');
        if ($e instanceof Throwable) {
            $msg = $e->getMessage() . ' ##code:' . $e->getCode() . ' ##file:' . $e->getFile() . ' ##line:' . $e->getLine();
            $logger->error($msg, $e->getTrace());
        } else {
            $logger->error(strval($e));
        }
    }
}


/**
 * 获取数据库连接
 * @return Medoo
 */
function getDb(): Medoo {
    return AppService::getDb();
}


/**
 * 获取Redis
 * @return Redis
 */
function getRedis(): Redis {
    return AppService::getRedis();
}
