<?php
namespace app\home\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Data extends Command
{
    protected function configure()
    {
        $this->setName('test')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("TestCommand:");
    }
}