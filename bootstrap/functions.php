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
use voku\helper\AntiXSS;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use function GuzzleHttp\Psr7\stream_for;

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


/**
 * curl下载
 * @param string $url
 * @param string $savefile 保存路径
 * @param array $param 其他参数:connect_timeout,timeout,
 * @param bool $returnContent 是否返回下载文件的内容
 * @return bool|string
 */
function curlDownload($url, $savefile=null, $param=[], $returnContent=true) {
    $res = '';
    if(empty($url) || !ValidateHelper::isUrl($url)) return $res;

    if(!empty($savefile) && file_exists($savefile)) {
        $res = $returnContent ? file_get_contents($savefile) : true; // content
        return $res;
    }elseif (empty($savefile)) {
        $savefile = tempnam(sys_get_temp_dir(), uniqid(date('ymd-His'), true));
    }

    $resource = @fopen($savefile, 'w');
    $stream   = stream_for($resource);

    $conntimeout = intval($param['connect_timeout'] ?? 5);
    $timeout = intval($param['timeout'] ?? 10);

    $client   = new HttpClient();
    $options  = [
        RequestOptions::VERIFY          => false, // cURL error SSL certificate problem
        RequestOptions::SINK            => $stream, // the body of a response
        RequestOptions::CONNECT_TIMEOUT => $conntimeout,    // request
        RequestOptions::TIMEOUT         => $timeout,    // response
    ];

    try{
        $response = $client->request('GET', $url, $options);
        $stream->close();
        @fclose($resource);

        if ($response->getStatusCode() === 200 && file_exists($savefile)) {
            $res = $returnContent ? file_get_contents($savefile) : true; // content
        }
    }catch (\Throwable $e) {

    }

    return $res;
}


/**
 * 获取远程图片宽高和大小
 * @param string $url 图片地址
 * @param string $type 获取方式:curl或fread
 * @param bool $isGetFilesize 是否获取远程图片的体积大小, 默认false不获取, 设置为 true 时 $type 将强制为 fread
 * @param int $length 读取长度
 * @param int $times 尝试次数
 * @param null $handle
 * @return bool|mixed
 */
function getRemoteImageSize($url, $type = 'curl', $isGetFilesize = false, $length=168, $times =1, $handle=null) {
    // 若需要获取图片体积大小则默认使用 fread 方式
    $type = $isGetFilesize ? 'fread' : $type;
    $handle = ($type == 'fread' && empty($handle)) ? fopen($url, 'rb') : null;
    if (!is_null($handle)) {
        // 或者使用 socket 二进制方式读取, 需要获取图片体积大小最好使用此方法
        if (! $handle) return false;
        // 只取头部固定长度168字节数据
        $dataBlock = fread($handle, $length);
    }else{
        // 据说 CURL 能缓存DNS 效率比 socket 高
        $ch = curl_init($url);
        // 超时设置
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 取前面 168 个字符 通过四张测试图读取宽高结果都没有问题,若获取不到数据可适当加大数值
        curl_setopt($ch, CURLOPT_RANGE, "0-{$length}");
        // 跟踪301跳转
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        // 返回结果
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $dataBlock = curl_exec($ch);
        curl_close($ch);
        var_dump($length, $url, $dataBlock);
    }
    if(empty($dataBlock)) return false;

    // 将读取的图片信息转化为图片路径并获取图片信息,经测试,这里的转化设置 jpeg 对获取png,gif的信息没有影响,无须分别设置
    // 有些图片虽然可以在浏览器查看但实际已被损坏可能无法解析信息
    $str64 = base64_encode($dataBlock);
    $size = getimagesize('data:image/jpeg;base64,'. $str64);
    if(empty($size)) {
        if($times<3) {
            $result = getRemoteImageSize($url, $type, $isGetFilesize, $length *10, ($times+1), $handle);
            return $result;
        }
        return false;
    }

    $result['width'] = $size[0];
    $result['height'] = $size[1];

    // 是否获取图片体积大小
    if ($isGetFilesize) {
        // 获取文件数据流信息
        $meta = stream_get_meta_data($handle);
        // nginx 的信息保存在 headers 里，apache 则直接在 wrapper_data
        $dataInfo = isset($meta['wrapper_data']['headers']) ? $meta['wrapper_data']['headers'] : $meta['wrapper_data'];
        foreach ($dataInfo as $va) {
            if ( preg_match('/length/iU', $va)) {
                $ts = explode(':', $va);
                $result['size'] = trim(array_pop($ts));
                break;
            }
        }
    }

    if ($type == 'fread' && $handle) fclose($handle);

    return $result;
}


/**
 * 时长格式化
 * @param int $seconds 秒
 * @param bool $useSeparator 使用`"分隔符
 * @return string
 */
function secondFormat($seconds, $useSeparator=false){
    if($useSeparator) {
        if($seconds<60) {
            return "{$seconds}\"";
        }
        $minu = intval($seconds / 60);
        $diff = $seconds % 60;

        return "{$minu}'{$diff}\"";
    }else{
        $iz=floor($seconds/60);
        $hz=floor($iz/60);
        $dz=floor($hz/24);
        /* 秒 */
        $s=$seconds%60;
        /* 分 */
        $i=floor($iz%60);
        /* 时 */
        $h=floor($hz/24);
        /* 天 */

        if($seconds<60){
            return $seconds.'秒';
        }else if($iz<60){
            return $iz.'分'.$s.'秒';
        }else if($hz<24){
            return $hz.'小时'.$i.'分'.$s.'秒';
        }else{
            return $dz.'天'.$h.'小时'.$i.'分'.$s.'秒';
        }
    }
}


/**
 * 获取AntiXSS
 * @return AntiXSS
 */
function getXssObj() {
    static $obj;

    if(is_null($obj)) {
        $obj = new AntiXSS();
    }

    return $obj;
}