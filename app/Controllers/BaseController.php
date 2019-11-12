<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2019/11/12
 * Time: 10:03
 * Desc:
 */


namespace App\Controllers;

use Lkk\LkkObject;

class BaseController extends  LkkObject {

    /**
     * API输出结果
     * @var array
     */
    public $result = [
        'status' => false,
        'msg' => '',
        'code' => 200,
        'data' => []
    ];


    public function __construct(array $vars = []) {
        parent::__construct($vars);

    }


    public function __destruct() {

    }


    /**
     * 递归去空格
     * @param array|mixed $data
     * @param bool $replaceSpace 是否替换多个空格和换行符
     * @return array|string
     */
    public static function recursionTrim($data = [], bool $replaceSpace=false) {
        if(is_array($data)) {
            foreach ($data as &$item) {
                $item = self::recursionTrim($item, $replaceSpace);
            }
            unset($item);
        }elseif (is_string($data)) {
            $chSpace = '　';
            if($replaceSpace) {
                //去除多余的空格和换行符，只保留一个
                $data = str_replace($chSpace, ' ', $data);
                $data = preg_replace("/([\s]{2,})/","\\1",$data);
            }

            $data = trim($data);
            //去除首尾中文空格
            if(mb_strpos($data, $chSpace)===0) {
                $data = mb_ereg_replace('(^(　)+([ \r\n\t])*)|(([ \r\n\t])*(　)+$)', '', $data);
            }
        }

        return $data;
    }


    /**
     * json输出
     * @param $data
     * @param string $callback js回调
     */
    public function jsonOutput($data, $callback='') {
        $data = json_encode($data);

        //jsonp
        if(empty($callback)) $callback = trim($_REQUEST['callback']??'');
        if($callback) $data = $callback . '(' . $data . ')';

        die($data);
    }


    /**
     * API调用输出
     * @param $data
     * @param string $callback js回调
     */
    public function end($callback='') {
        $this->jsonOutput($this->result, $callback);
    }



    /**
     * 返回-操作成功
     * @param array $data 数据
     * @param string $msg 提示消息
     * @param string $callback js回调
     */
    public function success($data=[], $msg='success', $callback='') {
        if($data === false || $data === true) {
            $this->result['data'] = [];
        }elseif (is_array($data) || is_object($data)) {
            $this->result['data'] = array_merge((array)$this->result['data'], (array)$data);
        }else{
            $this->result['data'] = strval($data);
        }

        $this->result['status'] = true;
        $this->result['msg'] = $msg;
        $this->result['code'] = 200;

        return $this->end($callback);
    }


    /**
     * 返回-操作失败
     * @param string|array $code 错误码/错误消息
     * @param array $data 数据
     * @param string $callback js回调
     */
    public function fail($code='', $data=[], $callback='') {
        if(!empty($data)) {
            if(is_array($data) || is_object($data)) {
                $this->result['data'] = array_merge($this->result['data'], (array)$data);
            }else{
                $this->result['data'] = strval($data);
            }
        }

        if(is_array($code)) {
            $msg = end($code);
            $code = reset($code);
        }else{
            $msg = lang($code);
        }

        $codeNo = ($code!=$msg && is_numeric($code)) ? intval($code) : 400;
        if($codeNo!=400 && $codeNo>=200 && $codeNo<=500) {
            http_response_code($codeNo);
        }

        $this->result['status'] = false;
        $this->result['msg'] = $msg;
        $this->result['code'] = $codeNo;

        return $this->end($callback);
    }




    /**
     * 获取参数值，先get后post(不包括cookie)
     * @param string|null $key
     * @param null $default
     * @param bool $xss
     * @return array|mixed|string
     */
    public static function getRequest(string $key=null, $default=null, $xss=true) {
        $data = self::recursionTrim(array_merge($_GET, $_POST), true);
        if($key==='' || is_null($key)) {
            if($xss) {
                return getXssObj()->xss_clean($data);
            }
            return $data;
        }

        $val = self::recursionTrim(($data[$key] ?? $default), true);
        if($xss) {
            $val = getXssObj()->xss_clean($val);
        }

        return $val;
    }


    /**
     * 获取post参数值
     * @param string|null $key
     * @param null $default
     * @param bool $xss
     * @return array|mixed|string
     */
    public static function getPost(string $key=null, $default=null, $xss=true) {
        $data = self::recursionTrim($_POST, true);
        if($key==='' || is_null($key)) {
            if($xss) {
                return getXssObj()->xss_clean($data);
            }
            return $data;
        }

        $val = self::recursionTrim(($data[$key] ?? $default), true);
        if($xss) {
            $val = getXssObj()->xss_clean($val);
        }

        return $val;
    }


    /**
     * 获取get参数值
     * @param string|null $key
     * @param null $default
     * @param bool $xss
     * @return array|mixed|string
     */
    public static function getGet(string $key=null, $default=null, $xss=true) {
        $data = self::recursionTrim($_GET, true);
        if($key==='' || is_null($key)) {
            if($xss) {
                return getXssObj()->xss_clean($data);
            }
            return $data;
        }

        $val = self::recursionTrim(($data[$key] ?? $default), true);
        if($xss) {
            $val = getXssObj()->xss_clean($val);
        }

        return $val;
    }


    public function indexAction() {
        die('It\'s time now:'. date('Y-m-d H:i:s'));
    }


}