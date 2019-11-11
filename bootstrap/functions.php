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
use Lkk\Helpers\CommonHelper;
use Lkk\Helpers\UrlHelper;
use Lkk\Helpers\ValidateHelper;

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


/**
 * 获取站点URL,结尾带/
 * @param string|null $url
 * @return string
 */
function getSiteUrl(string $url=null) {
    $res = '';
    if(empty($url)) $url = CommonHelper::getUrl();
    $url = UrlHelper::formatUrl($url);
    if(ValidateHelper::isUrl($url)) {
        $arr = parse_url($url);
        if(empty($port)) $port = $arr['port'] ?? null;
        if(empty($scheme)) $scheme = $arr['scheme'] ?? null;
        if(!empty($port) && !in_array($port, [80, 443])) {
            $res = "{$scheme}://{$arr['host']}:{$port}/";
        }else{
            $res = "{$scheme}://{$arr['host']}/";
        }

        $res = strtolower($res);
    }

    return $res;
}


/**
 * 生成URL
 * @param string $uri URI
 * @param array $params GET参数
 * @return string
 */
function makeUrl(string $uri=null, array $params=[]) {
    $hasHttp = stripos($uri, '://');
    if($hasHttp && empty($params)) return $uri;

    $url = getSiteUrl() . ltrim($uri, '/');
    if(strpos($url, '?')) {
        $arr = parse_url($url);
        parse_str($arr['query'], $pam);

        $url = str_replace($arr['query'], '', $url);
        $params = array_merge($pam, $params);
    }

    $query = empty($params) ? '' : http_build_query($params);
    if(!strpos($url,'?') && !empty($query)) $url .= '?';

    $url = $url . $query;
    unset($uri, $params, $arr, $pam, $query);

    return $url;
}


/**
 * 去除emoji表情符
 * @param $str
 * @return mixed
 */
function removeEmoji($str){
    $str = preg_replace_callback(
        '/./u',
        function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        },
        $str);
    return $str;
}