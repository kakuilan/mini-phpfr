<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2019/11/10
 * Time: 14:23
 * Desc: 应用服务类
 */

namespace App\Services;

use Medoo\Medoo;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Redis;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;
use function FastRoute\cachedDispatcher;
use App\Controllers\Home;
use PDOException;
use RedisException;
use Exception;
use Throwable;

class AppService extends ServiceBase {


    /**
     * 配置文件目录
     * @var string
     */
    private static $confDir;

    /**
     * 应用配置
     * @var array
     */
    private static $conf;


    /**
     * 数据库连接实例
     * @var Medoo
     */
    private static $db;


    /**
     * redis连接实例
     * @var Redis
     */
    private static $redis;


    /**
     * 设置配置文件目录
     * @param string|null $dir
     */
    public static function setConfDir(string $dir=null) {
        if(!empty($dir)) {
            self::$confDir = $dir;
        }
    }


    /**
     * 载入配置
     */
    private static function loadConf() {
        if(is_null(self::$conf)) {
            $dir = empty(self::$confDir) ? CONFDIR : self::$confDir;
            $file = 'conf.yml';
            try {
                self::$conf = Yaml::parseFile($dir . $file);
            } catch (ParseException $exception) {
                die("config file [{$file}] does not exist.");
            }
        }
    }


    /**
     * 获取配置
     * @param string|null $key 配置键,为空则取全部配置
     * @return mixed|null
     */
    public static function getConf(string $key=null) {
        $res = $key ? (self::$conf[$key] ?? null) : self::$conf;
        return $res;
    }


    /**
     * 连接数据库
     */
    private static function connDb() {
        if(is_null(self::$db)) {
            $conf = self::getConf('database');

            try{
                self::$db = new Medoo([
                    'database_type' => $conf['type'],
                    'database_name' => $conf['dbname'],
                    'server' => $conf['host'],
                    'username' => $conf['user'],
                    'password' => $conf['password'],
                    'charset' => $conf['charset'],
                    'port' => $conf['port'],
                    'prefix' => $conf['prefix'],
                ]);
            }catch (Exception $e) {
                die("database [{$conf['type']}] connection fail.");
            }
        }
    }


    /**
     * 获取数据库连接实例
     * @return Medoo
     */
    public static function getDb() {
        return self::$db;
    }


    /**
     * 连接redis
     */
    private static function connRedis() {
        if(is_null(self::$redis)) {
            $conf = self::getConf('redis');

            try {
                self::$redis = new Redis();
                self::$redis->connect($conf['host'], $conf['port'], 1, null, 100);

                // 验证
                if(!empty($conf['password'])) {
                    self::$redis->auth($conf['password']);
                }

                // 选项
                self::$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
                self::$redis->setOption(Redis::OPT_PREFIX, strval($conf['prefix']));
                self::$redis->select(intval($conf['select']));
            }catch (Exception $e) {
                die("redis connection fail.");
            }
        }
    }


    /**
     * 获取redis连接实例
     * @return Redis
     */
    public static function getRedis() {
        return self::$redis;
    }


    /**
     * 获取日志对象
     * @param string|null $logname
     * @return Logger
     */
    public static function getLogger(string $logname = null) {
        static $loggers;
        if(empty($logname)) $logname = 'debug';

        if(!isset($loggers[$logname]) || is_null($loggers[$logname])) {
            $logfile = LOGDIR . "{$logname}.log";
            $conf = self::getConf('logs');

            $logger = new Logger($logname);
            $logger->pushHandler(new RotatingFileHandler($logfile , intval($conf['log_max_files']), Logger::INFO));
            $loggers[$logname] = $logger;
        }

        return $loggers[$logname];
    }


    /**
     * 应用初始化
     */
    public static function init() {
        self::loadConf();
        self::connDb();
        self::connRedis();
    }


    /**
     * 路由分发
     */
    public static function dispactch() {
        $dispatcher = cachedDispatcher(function(RouteCollector $r) {
            $r->addRoute('GET', '/', 'Home@index');
            $r->addRoute('GET', '/home', 'Home@index');
            $r->addRoute('GET', '/home/index', 'Home@index');
            $r->addRoute('GET', '/home/home', 'Home@home');
        }, [
            'cacheFile' => RUNTDIR . 'cache/route', // 缓存路径,必须设置
            'cacheDisabled' => DEBUG_OPEN, // 是否禁用缓存
        ]);

        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // 404 Not Found
                self::notfound404();
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                // 405 Method Not Allowed
                $controller = new Home();
                $controller->fail(405);
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                list($class, $action) = explode("@", $handler, 2);

                $action .= 'Action';
                $ctlCls = '\App\Controllers\\' . ucfirst($class);

                if(class_exists($ctlCls)) {
                    $ctlObj = new $ctlCls;
                    if(method_exists($ctlObj, $action)) {
                        call_user_func_array([$ctlObj, $action], []);
                    }else{
                        self::notfound404();
                    }
                }else{
                    self::notfound404();
                }
                break;
        }
    }


    /**
     * 404页面
     */
    private static function notfound404() {
        $controller = new Home();
        $controller->fail(404);
    }



    /**
     * 运行web应用
     */
    public static function runWebApp() {
        self::init();
        self::dispactch();
    }

}