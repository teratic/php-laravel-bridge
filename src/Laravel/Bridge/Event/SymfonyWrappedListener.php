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

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class SymfonyWrappedListener
 *
 * @package TTIC\Laravel\Bridge\Event
 * @author Marcos Lois Bermúdez <marcos.lois@teratic.com>
 *
 * @codeCoverageIgnore
 * @internal
 */
class SymfonyWrappedListener
{
    /**
     * @var array|callable Wrapped Symfony listener callback
     */
    protected $listener;

    /**
     * SymfonyWrappedListener constructor.
     * @param $listener
     */
    public function __construct($listener)
    {
        $this->listener = $listener;
    }


    /**
     * Dispatch a Event to a Symfony listener.
     *
     * @param \Symfony\Component\EventDispatcher\Event $event The event to dispatch
     * @param string $eventName Event name
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher The event dispatcher
     * @return void
     */
    public function __invoke(Event $event, $eventName, EventDispatcherInterface $dispatcher) {
        call_user_func($this->listener, $event, $eventName, $dispatcher);
    }

    /**
     * Get the wrapped Symfony listener.
     *
     * @return callable
     */
    public function getListener()
    {
        return $this->listener;
    }


}
