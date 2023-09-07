<?php

namespace Ting\Think\Workflow;

use think\App;
use think\Service;
use Ting\Think\Workflow\Commands\WorkflowDumpCommand;

class WorkflowService extends Service
{
    protected $commands = [
        WorkflowDumpCommand::class,
    ];

    /**
     * Bootstrap the application services...
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);

        $this->app->bind('workflow', function (App $app) {
            $workflowConfigs = $app->config->get('workflow', []);
            $registryConfig  = $app->config->get('workflow_registry');

            return new WorkflowRegistry($workflowConfigs, $registryConfig, $app->event);
        });
    }
}