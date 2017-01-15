<?php
/*
* The MIT License (MIT)
* Copyright © 2017 Marcos Lois Bermudez <marcos.lois@teratic.com>
* *
* Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
* documentation files (the “Software”), to deal in the Software without restriction, including without limitation
* the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
* and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
* *
* The above copyright notice and this permission notice shall be included in all copies or substantial portions
* of the Software.
* *
* THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
* TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
* THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
* CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
* DEALINGS IN THE SOFTWARE.
*/

namespace TTIC\Laravel\Bridge\Event;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Events\Dispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * A event dispatcher that extends Laravel dispatcher and implements
 * Symfony EventDispatcherInterdace.
 *
 * The Laravel events that reach a Synfony listener are wrapped in a
 * GenericEvent object.
 *
 * The Symfony events that reach a Laravel listener are delivered as
 * is with only one argument with the Event object.
 *
 * @package TTIC\Laravel\Bridge\Event
 * @author Marcos Lois Bermúdez <marcos.lois@teratic.com>
 */
class EventDispatcher extends Dispatcher implements EventDispatcherInterface
{
    /**
     * {@inheritDoc}
     */
    public function fire($event, $payload = [], $halt = false)
    {
        // When the given "event" is actually an object we will assume it is an event
        // object and use the class as the event name and this event itself as the
        // payload to the handler, which makes object based events quite simple.
        if (is_object($event)) {
            list($payload, $event) = [[$event], get_class($event)];
        }

        $responses = [];

        // If an array is not given to us as the payload, we will turn it into one so
        // we can easily use call_user_func_array on the listeners, passing in the
        // payload to each of them so that they receive each of these arguments.
        if (!is_array($payload)) {
            $payload = [$payload];
        }

        $this->firing[] = $event;

        if (isset($payload[0]) && $payload[0] instanceof ShouldBroadcast) {
            $this->broadcastEvent($payload[0]);
        }

        foreach ($this->getListeners($event) as $listener) {
            // Check for a Symfony event listener
            if ($listener instanceof SymfonyWrappedListener) {
                // Wrap event if not Symfony one but only one time
                if (!isset($symfonyEvent)) {
                    $symfonyEvent = (isset($payload[0]) && ($payload[0] instanceof Event)) ?
                        $payload[0] : new GenericEvent('Laravel Wrapped Event', $payload);
                }

                $listener($symfonyEvent, $event, $this);

                if ($symfonyEvent->isPropagationStopped()) {
                    break;
                }

                $responses[] = null;
                continue;
            }

            // Laravel listener
            $response = call_user_func_array($listener, $payload);

            // If a response is returned from the listener and event halting is enabled
            // we will just return this response, and not call the rest of the event
            // listeners. Otherwise we will add the response on the response list.
            if (!is_null($response) && $halt) {
                array_pop($this->firing);

                return $response;
            }

            // If a boolean false is returned from a listener, we will stop propagating
            // the event to any further listeners down in the chain, else we keep on
            // looping through the listeners and firing every one in our sequence.
            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        array_pop($this->firing);

        return $halt ? null : $responses;
    }

    /**
     * {@inheritDoc}
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        // Wrap a Laravel event in a Symfony GenericEvent Object
//        $this->listen($eventName, function () use ($listener) {
//            $args = func_get_args();
//
//            // If not a Symfony Event wrap it
//            if (!isset($args[0]) || !($args[0] instanceof Event)) {
//                $event = new GenericEvent(null, $args);
//                $args = [$event];
//            }
//            $result = call_user_func_array($listener, $args);
//
//            // Check if Symfony listener has stopped the propagation
//            if (isset($event) && $event->isPropagationStopped()) {
//                $result = false;
//            }
//
//            return $result;
//        }, $priority);
        $this->listen($eventName, new SymfonyWrappedListener($listener), $priority);
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($eventName, Event $event = null)
    {
        if (null === $event) {
            $event = new Event();
        }

        $this->fire($eventName, $event);

        return $event;
    }

    /**
     * {@inheritDoc}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->addListener($eventName, array($subscriber, $params));
            } elseif (is_string($params[0])) {
                $this->addListener($eventName, array($subscriber, $params[0]), isset($params[1]) ? $params[1] : 0);
            } else {
                foreach ($params as $listener) {
                    $this->addListener($eventName, array($subscriber, $listener[0]), isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_array($params) && is_array($params[0])) {
                foreach ($params as $listener) {
                    $this->removeListener($eventName, array($subscriber, $listener[0]));
                }
            } else {
                $this->removeListener($eventName, array($subscriber, is_string($params) ? $params : $params[0]));
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function removeListener($eventName, $listener)
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $key => $val) {
                if ($listener == ($val instanceof SymfonyWrappedListener ? $val->getListener() : $val)) {
                    unset($this->listeners[$eventName][$priority][$key], $this->sorted[$eventName]);
                    if (!count($this->listeners[$eventName][$priority])) {
                        unset($this->listeners[$eventName][$priority]);
                    }
                    if (!count($this->listeners[$eventName])) {
                        unset($this->listeners[$eventName]);
                    }
                    return;
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getListenerPriority($eventName, $listener)
    {
        if (!isset($this->listeners[$eventName])) {
            return null;
        }

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            $listeners = array_map(function ($listener) {
                return $listener instanceof SymfonyWrappedListener ?
                    $listener->getListener() : $listener;
            }, $listeners);
            if (false !== in_array($listener, $listeners, true)) {
                return $priority;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners($eventName = null)
    {
        if (null !== $eventName) {
            return parent::getListeners($eventName);
        }

        foreach ($this->listeners as $eventName => $eventListeners) {
            if (!isset($this->sorted[$eventName])) {
                $this->sortListeners($eventName);
            }
        }

        return array_filter($this->sorted);
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners($eventName = null)
    {
        return parent::hasListeners($eventName);
    }
}
