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
use Redis;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
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
     * 应用初始化
     */
    public static function init() {
        self::loadConf();
        self::connDb();
        self::connRedis();

    }


    /**
     * 运行web应用
     */
    public static function runWebApp() {
        self::init();
        echo 'hello world';


    }

}