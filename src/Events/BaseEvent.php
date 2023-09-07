<?php

namespace Ting\Think\Workflow\Events;

use Ting\Think\Workflow\Facades\Workflow;
use Symfony\Component\Workflow\Event\Event;

/**
 * @method \Symfony\Component\Workflow\Marking getMarking()
 * @method object getSubject()
 * @method \Symfony\Component\Workflow\Transition getTransition()
 * @method \Symfony\Component\Workflow\WorkflowInterface getWorkflow()
 * @method string getWorkflowName()
 * @method mixed getMetadata(string $key, $subject)
 */
abstract class BaseEvent extends Event
{
    protected ?Event $symfonyProxyEvent = null;

    public function __serialize(): array
    {
        return [
            'base_event_class' => get_class($this),
            'subject'          => $this->getSubject(),
            'marking'          => $this->getMarking(),
            'transition'       => $this->getTransition(),
            'workflow'         => [
                'name' => $this->getWorkflowName(),
            ],
        ];
    }

    public function __unserialize(array $data): void
    {
        $workflowName = $data['workflow']['name'] ?? null;
        parent::__construct(
            $data['subject'],
            $data['marking'],
            $data['transition'],
            Workflow::get($data['subject'], $workflowName)
        );
    }

    /**
     * Creates a new instance from the base Symfony event
     */
    public static function newFromBase(Event $symfonyEvent)
    {
        $proxyEvent = new static(
            $symfonyEvent->getSubject(),
            $symfonyEvent->getMarking(),
            $symfonyEvent->getTransition(),
            $symfonyEvent->getWorkflow(),
            $symfonyEvent->getContext()
        );

        $proxyEvent->setRawEvent($symfonyEvent);

        return $proxyEvent;
    }

    protected function setRawEvent(Event $symfonyEvent): void
    {
        $this->symfonyProxyEvent = $symfonyEvent;
    }
}