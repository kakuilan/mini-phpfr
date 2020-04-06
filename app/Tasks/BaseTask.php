<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2020/4/6
 * Time: 14:25
 * Desc:
 */

namespace App\Tasks;

use Kph\Objects\StrictObject;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BaseTask
 * @package App\Tasks
 */
class BaseTask extends StrictObject {


    /**
     * cli输出
     * @var OutputInterface
     */
    protected $output = null;


    /**
     * 当前动作
     * @var string
     */
    private $action = '';


    /**
     * 设置output对象
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void {
        $this->output = $output;
    }


    /**
     * 设置动作
     * @param string $action
     */
    public function setAction(string $action): void {
        $this->action = $action;
    }


}