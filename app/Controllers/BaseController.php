<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/4
 * Time: 10:26
 * Desc:
 */

namespace App\Controllers;

use Kph\Helpers\StringHelper;
use Kph\Objects\StrictObject;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use App\Services\AppService;
use Exception;

/**
 * Class BaseController
 * @package App\Controllers
 */
class BaseController extends StrictObject {

    /**
     * API输出结果
     * @var array
     */
    public $result = [
        'status' => false,
        'msg'    => '',
        'code'   => 200,
        'data'   => []
    ];


    /**
     * 当前动作
     * @var string
     */
    private $action = '';


    /**
     * 视图数据
     * @var array
     */
    protected $viewData = [];


    /**
     * 视图引擎对象
     * @var Environment
     */
    private $view;


    /**
     * BaseController constructor.
     * @param array $vars
     * @throws Exception
     */
    public function __construct(array $vars = []) {
        parent::__construct($vars);
    }


    /**
     * 设置动作
     * @param string $action
     */
    public function setAction(string $action): void {
        $this->action = $action;
    }

    /**
     * 获取动作
     * @return string
     */
    public function getAction(): string {
        return $this->action;
    }


    /**
     * 递归去空格
     * @param array|string $data
     * @param bool $replaceSpace 是否替换多个空格和换行符
     * @return array|string
     */
    public static function recursionTrim($data = [], bool $replaceSpace = false) {
        if (is_array($data)) {
            foreach ($data as &$item) {
                $item = self::recursionTrim($item, $replaceSpace);
            }
            unset($item);
        } elseif (is_string($data)) {
            $chSpace = '　';
            if ($replaceSpace) {
                //去除多余的空格和换行符，只保留一个
                $data = str_replace($chSpace, ' ', $data);
                $data = preg_replace("/([\s]{2,})/", "\\1", $data);
            }

            $data = trim($data);
            //去除首尾中文空格
            if (mb_strpos($data, $chSpace) === 0) {
                $data = mb_ereg_replace('(^(　)+([ \r\n\t])*)|(([ \r\n\t])*(　)+$)', '', $data);
            }
        }

        return $data;
    }


    /**
     * json输出
     * @param mixed $data 数据
     * @param string $callback js回调
     */
    public function jsonOutput($data, string $callback = ''): void {
        $data = json_encode($data);

        //jsonp
        if (empty($callback))
            $callback = trim($_REQUEST['callback'] ?? '');
        if ($callback) {
            $data = $callback . '(' . $data . ')';
        }

        die($data);
    }


    /**
     * API调用输出
     * @param string $callback
     */
    public function end($callback = '') {
        $this->jsonOutput($this->result, $callback);
    }


    /**
     * 返回-操作成功
     * @param array|mixed $data 数据
     * @param string $msg 提示消息
     * @param string $callback js回调
     */
    public function success($data = [], string $msg = 'success', string $callback = '') {
        if (is_bool($data)) {
            $this->result['data'] = [];
        } elseif (is_array($data) || is_object($data)) {
            $this->result['data'] = array_merge((array)$this->result['data'], (array)$data);
        } else {
            $this->result['data'] = strval($data);
        }

        $this->result['status'] = true;
        $this->result['msg']    = $msg;
        $this->result['code']   = 200;

        return $this->end($callback);
    }


    /**
     * 返回-操作失败
     * @param string $code 错误码/错误消息
     * @param array|string $data 数据
     * @param string $callback js回调
     */
    public function fail($code = '', $data = [], string $callback = '') {
        if (!empty($data)) {
            if (is_array($data) || is_object($data)) {
                $this->result['data'] = array_merge($this->result['data'], (array)$data);
            } else {
                $this->result['data'] = strval($data);
            }
        }

        if (is_array($code)) {
            $msg  = end($code);
            $code = reset($code);
        } else {
            $msg = lang(strval($code));
        }

        $codeNo = ($code != $msg && is_numeric($code)) ? intval($code) : 400;
        if ($codeNo != 400 && $codeNo >= 200 && $codeNo <= 500) {
            http_response_code($codeNo);
        }

        $this->result['status'] = false;
        $this->result['msg']    = $msg;
        $this->result['code']   = $codeNo;

        return $this->end($callback);
    }


    /**
     * 获取参数值，先get后post(不包括cookie)
     * @param string|null $key
     * @param mixed|null $default
     * @param bool $xss
     * @return array|mixed|string
     */
    public static function getRequest(string $key = null, $default = null, bool $xss = true) {
        $data = self::recursionTrim(array_merge($_GET, $_POST), true);
        if ($key === '' || is_null($key)) {
            if ($xss) {
                return getXssObj()->xss_clean($data);
            }
            return $data;
        }

        $val = self::recursionTrim(($data[$key] ?? $default), true);
        if ($xss) {
            $val = getXssObj()->xss_clean($val);
        }

        return $val;
    }


    /**
     * 获取post参数值
     * @param string|null $key
     * @param mixed|null $default
     * @param bool $xss
     * @return array|mixed|string
     */
    public static function getPost(string $key = null, $default = null, bool $xss = true) {
        $data = self::recursionTrim($_POST, true);
        if ($key === '' || is_null($key)) {
            if ($xss) {
                return getXssObj()->xss_clean($data);
            }
            return $data;
        }

        $val = self::recursionTrim(($data[$key] ?? $default), true);
        if ($xss) {
            $val = getXssObj()->xss_clean($val);
        }

        return $val;
    }


    /**
     * 获取get参数值
     * @param string|null $key
     * @param mixed|null $default
     * @param bool $xss
     * @return array|mixed|string
     */
    public static function getQuery(string $key = null, $default = null, bool $xss = true) {
        $data = self::recursionTrim($_GET, true);
        if ($key === '' || is_null($key)) {
            if ($xss) {
                return getXssObj()->xss_clean($data);
            }
            return $data;
        }

        $val = self::recursionTrim(($data[$key] ?? $default), true);
        if ($xss) {
            $val = getXssObj()->xss_clean($val);
        }

        return $val;
    }


    /**
     * 获取视图对象
     * @return Environment
     */
    public function getViewer(): Environment {
        if (is_null($this->view)) {
            $conf       = AppService::getConf('twig');
            $loader     = new FilesystemLoader(VIEWDIR);
            $this->view = new Environment($loader, [
                'cache'       => RUNTDIR . $conf['cache_dir'],
                'auto_reload' => true,
                'debug'       => DEBUG_OPEN,
            ]);
        }

        return $this->view;
    }


    /**
     * 赋值给模板
     * @param array $data 变量数组
     * @param bool $reset 是否重置变量
     */
    public function assignView(array $data, bool $reset = false): void {
        if ($reset) {
            foreach ($data as $key => $item) {
                $this->viewData[$key] = $item;
            }
        } else {
            $this->viewData = array_merge($this->viewData, $data);
        }
    }


    /**
     * 渲染视图模板
     * @param array $data 数据数组
     * @param string $template 模板名
     * @param bool $return 是否返回html
     * @return string
     */
    public function render(array $data = [], string $template = '', bool $return = false) {
        if (empty($template)) {
            $template = $this->getClassShortName() . "/" . $this->getAction();
        }
        $template = ltrim($template, '/');

        $file = $template . AppService::getConf('twig')['view_suffix'];
        if (!file_exists(VIEWDIR . $file)) {
            die("template [{$template}] not exists.");
        }

        try {
            $this->viewData = array_merge($this->viewData, $data);
            $html           = $this->getViewer()->render($file, $this->viewData);
        } catch (Exception $e) {
        }
        if ($return) {
            return $html;
        }

        die($html);
    }


    public function indexAction() {
        die('It\'s time now:' . date('Y-m-d H:i:s'));
    }


}