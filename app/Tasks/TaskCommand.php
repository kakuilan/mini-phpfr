<?php
/**
 * Created by PhpStorm.
 * User: kakuilan
 * Date: 2020/4/6
 * Time: 13:09
 * Desc:
 */

namespace App\Tasks;

use App\Services\AppService;
use Kph\Helpers\ArrayHelper;
use Kph\Helpers\StringHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Error;
use Exception;
use Throwable;

/**
 * Class TaskCommand
 * @package App\Tasks
 */
class TaskCommand extends Command {


    /**
     * 配置
     */
    protected function configure() {
        $this->setName('task')
            ->setDescription('Run command tasks')
            ->setHelp('This command execute cli tasks')
            ->addArgument('route', InputArgument::OPTIONAL, 'Route of the task')
            ->addArgument('args', InputArgument::IS_ARRAY, 'Arguments of the task');
    }


    /**
     * 执行
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $route = $input->getArgument('route');
        $args  = $input->getArgument('args');
        if (empty($args)) {
            $args = [];
        }

        $ret = 0;
        try {
            $this->runTask($output, strval($route), $args);
        } catch (Error $e) {
            $ret = 1;
            if (OP_DEBUG) {
                logException($e);
            }
        }

        return $ret;
    }


    /**
     * 运行任务
     * @param OutputInterface $output
     * @param string $route
     * @param array $args
     */
    protected function runTask(OutputInterface $output, string $route, array $args = []): void {
        $task = $action = 'main';
        if (!empty($route)) {
            $arr = StringHelper::multiExplode($route, '/', ':');
            if (isset($arr[0])) {
                $task = $arr[0];
            }
            if (isset($arr[1])) {
                $task = $arr[1];
            }
        }

        $taskClass = 'App\Tasks\\' . ucfirst($task);
        if (class_exists($taskClass)) {
            $ctlObj = new $taskClass;
            $method = $action . AppService::$actionSuffix;
            if (method_exists($ctlObj, $method)) {
                $ctlObj->setOutput($output);
                $ctlObj->setAction(strtolower($action));
                call_user_func_array([$ctlObj, $method], [$args]);
            } else {
                $output->writeln("{$taskClass}::{$method} not exist");
            }
        } else {
            $output->writeln("{$taskClass} not exist");
        }
    }


}