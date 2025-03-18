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
use App\Tasks\TaskCommand;
use Error;
use Exception;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Kph\Exceptions\BaseException;
use Kph\Helpers\OsHelper;
use Medoo\Medoo;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use PDO;
use Redis;
use RedisException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use function FastRoute\cachedDispatcher;

/**
 * Class AppService
 * @package App\Services
 */
class AppService extends ServiceBase {


    /**
     * 动作名称后缀
     * @var string
     */
    public static $actionSuffix = 'Action';


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
            $path = $dir . $file;
            if(!file_exists($path)) {
                die("config file [{$path}] does not exist.");
            }
            try {
                self::$conf = Yaml::parseFile($path);
            } catch (ParseException $exception) {
                die("parse config fail: ". $exception->getMessage());
            }
        }
    }


    /**
     * 获取配置
     * @param string|null $key 配置键,为空则取全部配置
     * @return array|mixed|null
     */
    public static function getConf(string $key = null) {
        return $key ? (self::$conf[$key] ?? null) : self::$conf;
    }


    /**
     * 获取日志对象
     * @param string|null $logName
     * @return Logger
     */
    public static function getLogger(string $logName = null): Logger {
        static $loggers;
        if (empty($logName)) {
            $logName = 'debug';
        }

        if (!isset($loggers[$logName])) {
            $logfile = LOGDIR . "{$logName}.log";
            $conf    = self::getConf('logs');

            $logger = new Logger($logName);
            $logger->pushHandler(new RotatingFileHandler($logfile, intval($conf['log_max_files']), Logger::INFO));
            $loggers[$logName] = $logger;
        }

        return $loggers[$logName];
    }


    /**
     * 连接DB
     */
    private static function connDb(): void {
        $conf = self::getConf('database');
        if ($conf['enable']) {
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
            } catch (Throwable $e) {
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
        $chk = !is_null(self::$db) && self::$db instanceof Medoo;
        if ($chk && OsHelper::isCliMode()) {
            $chk = self::$db->pdo->getAttribute(PDO::ATTR_SERVER_INFO) && self::$db->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
        }

        if (!$chk) {
            self::connDb();
        }

        return self::$db;
    }


    /**
     * 执行分块查询
     * @param int $size 每次数量
     * @param callable $callback 回调函数,形如 fn(array $rows, int $page):bool
     * @param string $table
     * @param $join
     * @param $columns
     * @param $where
     * @return int
     * @throws BaseException
     */
    public static function queryChunk(int $size, callable $callback, string $table, $join, $columns = null, $where = null): int {
        //查询一定要有排序
        $isColumnsOrder = isset($columns['ORDER']);
        $isWhereOrder   = isset($where['ORDER']);
        if (!$isColumnsOrder && !$isWhereOrder) {
            throw new BaseException('You must specify one order by');
        }

        $db    = self::getDb();
        $page  = 1;
        $total = 0;
        if ($size <= 0) {
            $size = 10;
        }

        do {
            $start      = ($page - 1) * $size;
            $limitWhere = [
                'LIMIT' => [$start, $size],
            ];

            if ($isColumnsOrder) {
                $columns = array_merge($columns, $limitWhere);
            } elseif ($isWhereOrder) {
                $where = array_merge($where, $limitWhere);
            }

            $rows  = $db->select($table, $join, $columns, $where);
            $count = count($rows);
            $total += $count;
            if ($count == 0) {
                break;
            }

            //回调
            if ($callback($rows, $page) === false) {
                return $total;
            }

            unset($rows);
            $page++;
        } while ($count == $size);

        return $total;
    }

    /**
     * 连接redis
     */
    private static function connRedis() {
        $conf = self::getConf('redis');
        if ($conf['enable']) {
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
            } catch (Throwable $e) {
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
                [$class, $action] = explode("@", $handler, 2);
                if (empty($class) || empty($action)) {
                    self::notfound404();
                }

                $method = $action . self::$actionSuffix;
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


    /**
     * 运行cli应用
     * @throws Exception
     */
    public static function runCliApp() {
        self::init();
        if (!OsHelper::isCliMode()) {
            die('deny!');
        }

        $app = new Application(APP_NAME, APP_VERSION);
        $app->add(new TaskCommand());
        $app->run();
    }


}