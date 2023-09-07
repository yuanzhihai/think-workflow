<?php

namespace Ting\Think\Workflow\Events;

class TransitionEvent extends BaseEvent
{
    public function __call($name, $arguments)
    {
        if (isset($this->symfonyProxyEvent) && method_exists($this->symfonyProxyEvent, $name)) {
            return call_user_func_array([$this->symfonyProxyEvent, $name], $arguments);
        } else {
            throw new \BadMethodCallException(sprintf('Method "%s" not found on "%s"', $name, get_class($this)));
        }
    }
}