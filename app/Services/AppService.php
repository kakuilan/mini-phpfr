<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2019/11/10
 * Time: 14:23
 * Desc:
 */

namespace App\Services;

class AppService extends ServiceBase {


    /**
     * 应用初始化
     */
    public static function init() {

    }


    /**
     * 运行web应用
     */
    public static function runWebApp() {
        self::init();
        echo 'hello world';
    }

}