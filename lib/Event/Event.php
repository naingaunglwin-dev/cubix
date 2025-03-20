<?php

namespace Cubix\Event;

use Cubix\Exception\Base\Format;
use Cubix\Supports\Collection\Collection;
use InvalidArgumentException;
use Cubix\Exception\Base\Missing;
use Cubix\Supports\DependencyInjector\DI;

class Event
{
    /**
     * Registered event listeners
     *
     * @var Collection
     */
    private Collection $listeners;

    /**
     * @var DI
     */
    private DI $injector;

    /**
     * Deferred events
     *
     * @var Collection
     */
    private Collection $deferredEvents;

    /**
     * Subscribed classes list
     *
     * @var Collection
     */
    private Collection $subscribed;

    /**
     * Event constructor
     */
    public function __construct()
    {
        $this->injector = new DI();
        $this->listeners      = collect([]);
        $this->deferredEvents = collect([]);
        $this->subscribed     = collect([]);
    }

    /**
     * Register an event listener
     *
     * @param string $event The name of the event to listen for
     * @param callable|array $listener The callback function to execute when the event is emitted
     * @param int $priority Priority to sort the listeners
     * @return Event
     */
    public function on(string $event, callable|array $listener, int $priority = 0): Event
    {
        $this->addListener($event, $listener, $priority);

        return $this;
    }

    /**
     * Register a one-time event listener
     *
     * @param string $event The name of the event to listen for
     * @param callable|array $listener The callback function to execute when the event is emitted
     * @param int $priority Priority to sort the listeners
     * @return Event
     */
    public function once(string $event, callable|array $listener, int $priority = 0): Event
    {
        $this->addListener($event, $listener, $priority, true);

        return $this;
    }

    /**
     * Get all the listeners for a given event or all of listeners
     *
     * @param string|null $event (optional) The name of the event
     *
     * @return mixed
     */
    public function getListeners(?string $event = null): mixed
    {
        if ($event) {

            $this->eventCannotBeEmpty($event);

            return $this->listeners->get($event) ?? [];
        }

        return $this->listeners->all();
    }

    /**
     * Remove all listeners for a given event
     *
     * @param string|null $event (optional) The name of the event
     *
     * @return void
     */
    public function removeListeners(?string $event = null): void
    {
        if ($event) {

            $this->eventCannotBeEmpty($event);

            $this->listeners->remove($event);
        } else {
            $this->listeners->clear();
        }
    }

    /**
     * Remove the given listener from given event
     *
     * @param string $event      The name of the event
     * @param callable $listener The listener to remove
     *
     * @return void
     */
    public function removeListener(string $event, callable $listener): void
    {
        $this->eventCannotBeEmpty($event);

        if ($this->listeners->has($event)) {
            foreach ($this->listeners->get($event) as $priority => $listeners) {
                foreach ($listeners as $key => $data) {
                    if (($key === 'once' && ($index = array_search($listener, $data, true)) !== false) ||
                        ($index = array_search($listener, $listeners, true)) !== false) {

                        if ($key === 'once') {
                            $this->listeners->remove("$event.$priority.$key.$index");
                        } else {
                            $this->listeners->remove("$event.$priority.$key");
                        }

                        $toRemove = "$event.$priority";

                        if (!$this->listeners->has("$toRemove.$key")) {
                           $this->listeners->remove("$toRemove.$key");
                        }
                        if (!$this->listeners->has($toRemove)) {
                            $this->listeners->remove($toRemove);
                        }

                        break 2;
                    }
                }
            }
        }
    }

    /**
     * Emit an event
     *
     * @param string $event  The name of the event to emit
     * @param mixed ...$args Parameters to pass to the event listeners
     *
     * @return void
     */
    public function emit(string $event, mixed ...$args): void
    {
        $this->eventCannotBeEmpty($event);

        if ($this->listeners->has($event)) {

            $e = collect(
                $this->listeners->filter(function ($value, $key) use ($event) {
                    if ($key === $event) return $value;
                })->values()->first()
            )->ksort()->all();

            foreach ($e as $priority => $listeners) {
                foreach ($listeners as $key => $data) {

                    if ($key === 'once') {
                        unset($e[$priority][$key]);

                        foreach ($data as $listener) {
                            $this->resolve($listener, ...$args);
                        }
                    } else {
                        $this->resolve($data, ...$args);
                    }
                }
            }

            $this->listeners->add($event, $e, true);
        }
    }

    /**
     * Defer an event to be emitted later
     *
     * @param string $event  The name of the event to defer
     * @param mixed ...$args Parameters to pass to the event listeners
     *
     * @return Event
     */
    public function defer(string $event, mixed ...$args): Event
    {
        $this->eventCannotBeEmpty($event);

        $this->deferredEvents->push(["event" => $event, "args" => $args]);

        return $this;
    }

    /**
     * Dispatch all deferred events
     *
     * @return void
     */
    public function dispatch4deferred(): void
    {
        if ($this->deferredEvents->isEmpty()) {
            return;
        }

        $this->deferredEvents->each(fn ($value, $key) => $this->emit($value['event'], ...$value['args']));
    }

    /**
     * Subscribe to events using an EventSubscriber
     *
     * @param EventSubscriber $subscriber The subscriber to register
     *
     * @return Event
     *
     * @throws Format If the subscriber event listeners are invalid
     * @throws Missing If a specified subscriber class does not exist
     */
    public function subscribe(EventSubscriber $subscriber): Event
    {
        $this->subscribeAction("subscribe", $subscriber);

        return $this;
    }

    /**
     * Unsubscribe from events using an EventSubscriber
     *
     * @param EventSubscriber $subscriber The subscriber to unregister
     *
     * @return Event
     */
    public function unsubscribe(EventSubscriber $subscriber): Event
    {
        if ($this->subscribed->has($subscriber::class)) {
            $this->subscribeAction("unsubscribe", $subscriber);
        }

        return $this;
    }

    /**
     * Handle subscription or unsubscription actions for an EventSubscriber
     *
     * @param string $type                The type of action ("subscribe" or "unsubscribe")
     * @param EventSubscriber $subscriber The subscriber to register or unregister
     *
     * @return void
     */
    private function subscribeAction(string $type, EventSubscriber $subscriber): void
    {
        $class  = $subscriber::class;

        if ($type === "subscribe") {
            $this->subscribed->push($class);
        } else {
            $this->subscribed = $this->subscribed->filter(function ($value, $key) use ($class) {
                if ($class !== $value) return $value;
            });
        }

        $events = $subscriber->getEvents();

        if (!empty($events)) {
            foreach ($events as $event => $listeners) {
                if ($type === "subscribe") {
                    if (is_array($listeners)) {
                        foreach ($listeners as $listener) {
                            $this->on($event, [$class, $listener]);
                        }
                    } else {
                        $this->on($event, [$class, $listeners]);
                    }
                } else {
                    $this->removeListeners($event);
                }
            }
        }
    }

    /**
     * Resolve the event to emit
     *
     * @param $listener
     * @param mixed ...$args
     *
     * @return void
     */
    private function resolve($listener, ...$args): void
    {
        if (is_array($listener)) {

            $this->check($listener);

            if (empty($args)) {
                $this->injector->method($listener[1], $listener[0]);
            } else {
                $this->createClass($listener[0]);

                call_user_func($listener, ...$args);
            }
        }  else {
            if (empty($args)) {
                $this->injector->callback($listener);
            } else {
                call_user_func($listener, ...$args);
            }
        }
    }

    /**
     * Instantiate a class and assign it to the provided variable
     *
     * @param mixed $class The class name to instantiate
     *
     * @return void
     */
    private function createClass(mixed &$class): void
    {
        if (is_string($class)) {
            $class = new $class();
        }
    }

    /**
     * Validate the event listener
     *
     * @param array $listener The event listener to validate
     *
     * @return void
     *
     * @throws Format If the listener array is invalid
     * @throws Missing If the specified class does not exist
     */
    private function check(array $listener): void
    {
        if (empty($listener) || !isset($listener[0]) || !isset($listener[1])) {
            throw new Format(
                sprintf(
                    "Incorrect array format is used, it must be [%s, 'method']",
                    "\\namespace\\controller"
                )
            );
        }

        if (!class_exists($listener[0])) {
            throw new Missing("Class {$listener[0]} does not exist");
        }
    }

    /**
     * Add the listeners
     *
     * @param string $event            The name of the event to listen for
     * @param callable|array $listener The callback function to execute when the event is emitted
     * @param int $priority            Priority to sort the listeners
     * @param bool $once I             s listener just for once
     *
     * @return void
     */
    private function addListener(string $event, callable|array $listener, int $priority = 0, bool $once = false): void
    {
        $this->eventCannotBeEmpty($event);

        if ($once) {
            $this->listeners->add("$event.$priority.once", [$listener]);
        } else {
            $new = $this->listeners->all();

            $new[$event][$priority][] = $listener;

            $this->listeners = collect($new);
        }
    }

    /**
     * Throw `InvalidArgumentException` on empty string of event name
     *
     * @param $event
     *
     * @return void
     */
    private function eventCannotBeEmpty($event): void
    {
        if ($event === '') {
            throw new InvalidArgumentException('Event name cannot be empty.');
        }
    }
}
