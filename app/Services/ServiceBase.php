<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/4
 * Time: 10:13
 * Desc:
 */

namespace App\Services;

use Kph\Services\BaseService;

class ServiceBase extends BaseService {

    public function __construct(array $vars = []) {
        parent::__construct($vars);
    }

}