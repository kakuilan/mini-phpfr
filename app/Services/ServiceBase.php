<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2019/11/10
 * Time: 14:20
 * Desc:
 */

namespace App\Services;

use Lkk\LkkService;

class ServiceBase extends LkkService {

    public $error = '';
    public $errno = 0;


    public function __construct(array $vars = []) {
        parent::__construct($vars);

    }


    public function __destruct() {
        parent::__destruct();
    }

}