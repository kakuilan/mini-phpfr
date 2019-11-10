<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2019/11/10
 * Time: 14:23
 * Desc: 应用服务类
 */

namespace App\Services;

use Symfony\Component\Yaml\Yaml;
use Medoo\Medoo;

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
            self::$conf = Yaml::parseFile($dir . 'conf.yml');
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
     * 应用初始化
     */
    public static function init() {
        self::loadConf();
        self::connDb();

    }


    /**
     * 运行web应用
     */
    public static function runWebApp() {
        self::init();
        echo 'hello world';

    }

}