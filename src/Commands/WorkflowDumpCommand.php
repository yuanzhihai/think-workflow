<?php

namespace Ting\Think\Workflow\Commands;

use Exception;
use think\console\Input;
use think\console\Output;
use think\console\Command;
use think\console\input\Option;
use think\console\input\Argument;
use Symfony\Component\Process\Process;
use Ting\Think\Workflow\Facades\Workflow;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Dumper\GraphvizDumper;
use Symfony\Component\Workflow\Dumper\StateMachineGraphvizDumper;

class WorkflowDumpCommand extends Command
{
    /**
     * Execute the console command.
     *
     * @param Input $input
     * @param Output $output
     *
     * @return int
     */
    public function execute(Input $input, Output $output): int
    {
        $config = $this->app->config;

        $workflowName = $input->getArgument('workflow');
        $format       = $input->getOption('format');
        $class        = $input->getOption('class');
        $config       = $config->get('workflow');
        //        $optionalPath = $input->getOption('path');
        $path = \root_path() . "{$workflowName}.{$format}";

        if (!isset($config[$workflowName])) {
            throw new Exception("Workflow {$workflowName} is not configured.");
        }

        if (false === array_search($class, $config[$workflowName]['supports'])) {
            throw new Exception("Workflow {$workflowName} has no support for class {$class}." .
                ' Please specify a valid support class with the --class option.');
        }

        $subject    = new $class();
        $workflow   = Workflow::get($subject, $workflowName);
        $definition = $workflow->getDefinition();

        $dumper = new GraphvizDumper();

        if ($workflow instanceof StateMachine) {
            $dumper = new StateMachineGraphvizDumper();
        }

        $dotCommand = ['dot', "-T{$format}", '-o', "{$workflowName}.{$format}"];

        $process = new Process($dotCommand);
        $process->setWorkingDirectory(\dirname($path));
        $process->setInput($dumper->dump($definition));
        $process->mustRun();

        return 0;
    }

    protected function configure()
    {
        $this->setName('workflow:dump')
            ->addArgument('workflow', Argument::REQUIRED, 'name of workflow from configuration')
            ->addOption('class', null, Option::VALUE_OPTIONAL, 'the support class name')
            ->addOption('format', null, Option::VALUE_OPTIONAL, 'the image format', 'png')
            ->addOption('disk', null, Option::VALUE_OPTIONAL, 'the storage disk name', 'local')
            ->addOption('path', null, Option::VALUE_OPTIONAL, 'the optional path within selected disk')
            ->setDescription('GraphvizDumper dumps a workflow as a graphviz file. You can convert the generated dot file with the dot utility (http://www.graphviz.org/):');
    }
}