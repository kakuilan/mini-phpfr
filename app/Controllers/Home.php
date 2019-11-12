<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2019/11/12
 * Time: 10:10
 * Desc:
 */

namespace App\Controllers;

class Home extends BaseController {

    public function homeAction() {
        $data = [
            'msg'=>'hello world!',
        ];
        $this->assignView($data);
        $this->render();
    }



}