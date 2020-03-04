<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/4
 * Time: 13:04
 * Desc:
 */

namespace App\Controllers;

class Home extends BaseController {

    public function homeAction() {
        $data = [
            'msg' => 'hello world!',
        ];
        $this->assignView($data);
        $this->render();
    }

}