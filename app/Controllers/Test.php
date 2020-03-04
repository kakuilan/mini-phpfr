<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/4
 * Time: 13:07
 * Desc:
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