<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2019/11/12
 * Time: 15:09
 * Desc: 测试
 */

namespace App\Controllers;

class Test extends BaseController {

    public function __construct(array $vars = []) {
        parent::__construct($vars);

        if(!DEBUG_OPEN) {
            $this->fail();
        }
    }


    public function postAction() {
        $this->success('This is post page.');
    }


}