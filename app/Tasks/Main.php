<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2020/4/6
 * Time: 14:42
 * Desc:
 */

namespace App\Tasks;

/**
 * Class Main
 * @package App\Tasks
 */
class Main extends BaseTask {


    /**
     * 主动作
     * @param array $args
     */
    public function mainAction(array $args = []) {
        $this->output->writeln('succeed!');
    }

}