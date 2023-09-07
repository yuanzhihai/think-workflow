<?php

namespace Ting\Think\Workflow\Events;

use think\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DispatcherAdapter implements EventDispatcherInterface
{
    private const EVENT_MAP = [
        'guard'      => GuardEvent::class,
        'leave'      => LeaveEvent::class,
        'transition' => TransitionEvent::class,
        'enter'      => EnterEvent::class,
        'entered'    => EnteredEvent::class,
        'completed'  => CompletedEvent::class,
        'announce'   => AnnounceEvent::class,
    ];

    protected $dispatcher;

    private $plainEvents;

    public function __construct(Event $dispatcher)
    {
        $this->dispatcher  = $dispatcher;
        $this->plainEvents = array_map(function ($event) {
            return "workflow.{$event}";
        }, array_keys(static::EVENT_MAP));
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param object $event The event to pass to the event handlers/listeners
     * @param string|null $eventNam e The name of the event to dispatch. If not supplied,
     *                               the class of $event should be used instead.
     *
     * @return object The passed $event MUST be returned
     */
    public function dispatch(object $event, ?string $eventName = null): object
    {
        $name = is_null($eventName) ? get_class($event) : $eventName;

        $eventToDispatch = $this->translateEvent($eventName, $event);

        // Only dispatch the class event once
        if ($this->shouldDispatchPlainClassEvent($eventName)) {
            $this->dispatcher->trigger($eventToDispatch);
        }

        // Dispatch with the Symfony dot syntax event names
        $this->dispatcher->trigger($name, $eventToDispatch);

        return $eventToDispatch;
    }

    private function shouldDispatchPlainClassEvent(?string $eventName = null)
    {
        if (!$eventName) {
            return false;
        }

        return in_array($eventName, $this->plainEvents);
    }

    private function translateEvent(?string $eventName, object $symfonyEvent): object
    {
        if (is_null($eventName)) {
            return WorkflowEvent::newFromBase($symfonyEvent);
        }

        $event = $this->parseWorkflowEventFromEventName($eventName);

        if (!$event) {
            return WorkflowEvent::newFromBase($symfonyEvent);
        }

        /** @var class-string<BaseEvent> $translatedEventClass */
        $translatedEventClass = static::EVENT_MAP[$event];

        return $translatedEventClass::newFromBase($symfonyEvent);
    }

    private function parseWorkflowEventFromEventName(string $eventName)
    {
        $eventSearch = preg_match('/\.(?P<event>' . implode('|', array_keys(static::EVENT_MAP)) . ')(\.|$)/i', $eventName, $eventMatches);

        if (!$eventSearch) {
            // no results or error
            return false;
        }
        $event = $eventMatches['event'] ?? false;

        if (!array_key_exists($event, static::EVENT_MAP)) {
            // fallback for no mapped event known
            return false;
        }

        return $event;
    }
}