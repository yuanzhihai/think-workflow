<?php

namespace Ting\Think\Workflow\Facades;

/**
 * @mixin \Ting\Think\Workflow\WorkflowRegistry
 */
class Workflow extends \think\Facade
{
    protected static function getFacadeClass()
    {
        return 'workflow';
    }
}