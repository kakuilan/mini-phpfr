<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/4
 * Time: 10:15
 * Desc:
 */

namespace App\Services;

use App\Controllers\Home;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Medoo\Medoo;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Redis;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use function FastRoute\cachedDispatcher;
use Exception;
use RedisException;
use Throwable;

/**
 * Class AppService
 * @package App\Services
 */
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
     * @param string $dir
     */
    public static function setConfDir(string $dir): void {
        if (!empty($dir)) {
            self::$confDir = $dir;
        }
    }


    /**
     * 载入配置
     */
    private static function loadConf(): void {
        if (is_null(self::$conf)) {
            $dir  = empty(self::$confDir) ? CONFDIR : self::$confDir;
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
     * @return array|mixed|null
     */
    public static function getConf(string $key = null) {
        $res = $key ? (self::$conf[$key] ?? null) : self::$conf;
        return $res;
    }


    /**
     * 获取日志对象
     * @param string|null $logname
     * @return Logger
     */
    public static function getLogger(string $logname = null): Logger {
        static $loggers;
        if (empty($logname)) {
            $logname = 'debug';
        }

        if (!isset($loggers[$logname]) || is_null($loggers[$logname])) {
            $logfile = LOGDIR . "{$logname}.log";
            $conf    = self::getConf('logs');

            $logger = new Logger($logname);
            $logger->pushHandler(new RotatingFileHandler($logfile, intval($conf['log_max_files']), Logger::INFO));
            $loggers[$logname] = $logger;
        }

        return $loggers[$logname];
    }


    /**
     * 连接DB
     */
    private static function connDb(): void {
        $conf = self::getConf('database');
        if (is_null(self::$db) && $conf['enable']) {
            try {
                self::$db = new Medoo([
                    'database_type' => $conf['type'],
                    'database_name' => $conf['dbname'],
                    'server'        => $conf['host'],
                    'username'      => $conf['user'],
                    'password'      => $conf['password'],
                    'charset'       => $conf['charset'],
                    'port'          => $conf['port'],
                    'prefix'        => $conf['prefix'],
                ]);
            } catch (Exception $e) {
                $msg = "database [{$conf['type']}] connection fail.";
                self::getLogger('error')->error($msg);
            }
        }
    }


    /**
     * 获取数据库连接实例
     * @return Medoo
     */
    public static function getDb(): Medoo {
        return self::$db;
    }


    /**
     * 连接redis
     */
    private static function connRedis() {
        $conf = self::getConf('redis');
        if (is_null(self::$redis) && $conf['enable']) {
            try {
                self::$redis = new Redis();
                self::$redis->connect($conf['host'], $conf['port'], 1, null, 100);

                // 验证
                if (!empty($conf['password'])) {
                    self::$redis->auth($conf['password']);
                }

                // 选项
                self::$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
                self::$redis->setOption(Redis::OPT_PREFIX, strval($conf['prefix']));
                self::$redis->select(intval($conf['select']));
            } catch (Exception $e) {
                $msg = "redis connection fail.";
                self::getLogger('error')->error($msg);
            }
        }
    }


    /**
     * 获取redis连接实例
     * @return Redis
     */
    public static function getRedis(): Redis {
        return self::$redis;
    }


    /**
     * 应用初始化
     */
    public static function init(): void {
        self::loadConf();
        self::connDb();
        self::connRedis();
    }


    /**
     * 路由分发
     * @throws Exception
     */
    public static function dispactch() {
        $dispatcher = cachedDispatcher(function (RouteCollector $r) {
            //默认路由
            $r->addRoute('GET', '/', 'Home@index');
            $r->addRoute('GET', '/home', 'Home@index');
            $r->addRoute('GET', '/home/index', 'Home@index');
            $r->addRoute('GET', '/home/home', 'Home@home');

            //自定义路由
            $routes = include CONFDIR . 'route.php';
            if (!empty($routes)) {
                foreach ($routes as $item) {
                    $method  = strtoupper($item['method'] ?? '');
                    $path    = trim($item['path'] ?? '');
                    $handler = trim($item['handler'] ?? '');
                    if (empty($path) || empty($handler)) {
                        continue;
                    }

                    $r->addRoute($method, $path, $handler);
                }
            }
        }, [
            'cacheFile'     => RUNTDIR . 'cache/route', // 缓存路径,必须设置
            'cacheDisabled' => DEBUG_OPEN, // 是否禁用缓存
        ]);

        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri        = $_SERVER['REQUEST_URI'];
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
                if (empty($class) || empty($action)) {
                    self::notfound404();
                }

                $method = $action . 'Action';
                $ctlCls = '\App\Controllers\\' . ucfirst($class);

                if (class_exists($ctlCls)) {
                    $ctlObj = new $ctlCls;
                    if (method_exists($ctlObj, $method)) {
                        $ctlObj->setAction(strtolower($action));
                        call_user_func_array([$ctlObj, $method], []);
                    } else {
                        self::notfound404();
                    }
                } else {
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