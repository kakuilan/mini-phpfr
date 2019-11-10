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

class AppService extends ServiceBase {

    private static $conf;


    /**
     * 载入配置
     */
    private static function loadConf() {
        if(is_null(self::$conf)) {
            self::$conf = Yaml::parseFile(CONFDIR . 'conf.yml');
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
     * 应用初始化
     */
    public static function init() {
        self::loadConf();

    }


    /**
     * 运行web应用
     */
    public static function runWebApp() {
        self::init();
        echo 'hello world';
    }

}