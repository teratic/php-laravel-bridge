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

namespace TTIC\Test\Laravel\Event;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Container\Container;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\EventDispatcher\GenericEvent;
use TTIC\Laravel\Bridge\Event\EventDispatcher;

/**
 * Mixed EventDispatcher Tests
 *
 * @covers \TTIC\Laravel\Bridge\Event\EventDispatcher
 */
class MixedEventDispatcherTest extends TestCase
{
    /**
     * @var \TTIC\Laravel\Bridge\Event\EventDispatcher
     */
    protected $events;

    /**
     * @before
     */
    public function setUpEventDispatcher()
    {
        $this->events = new EventDispatcher();
    }

    /**
     * @test
     */
    public function checkThatLaravelEventReachBothListeners()
    {
        // Given: A EventDispatcher with both a Laravel and a
        // Synfony Listener
        $events = $this->events;
        $eventArgs = ['OneArgument', 'Another one'];

        // Register a Laravel listener
        $events->listen('test.event', function () use ($eventArgs) {
            // Ensure we receive the same arguments
            $this->assertEquals($eventArgs, func_get_args());
        });

        // Register a Symfony listener
        $events->addListener('test.event', function (GenericEvent $event) use ($eventArgs) {
            // Ensure we receive GenericEvent with the same arguments
            $this->assertEquals($eventArgs, $event->getArguments());
        });

        // When: a Laravel event is fired
        $result = $events->fire('test.event', $eventArgs);

        // Then: Both listener receive the event with the same arguments
        $this->assertCount(2, $result);
    }

    /**
     * @test
     */
    public function checkThatSymfonyEventReachBothListeners()
    {
        // Given: A EventDispatcher with both a Laravel and a
        // Synfony Listener
        $events = $this->events;
        $eventFired = new GenericEvent(null, ['OneArgument', 'Another one']);
        $received = 0;

        // Register a Laravel listener
        $events->listen('test.event', function (GenericEvent $event) use ($eventFired, &$received) {
            // Ensure we receive the same arguments
            $this->assertEquals($eventFired, $event);
            $received++;
        });

        // Register a Symfony listener
        $events->addListener('test.event', function (GenericEvent $event) use ($eventFired, &$received) {
            // Ensure we receive GenericEvent with the same arguments
            $this->assertEquals($eventFired, $event);
            $received++;
        });

        // When: a Symfony event is dispatched
        $result = $events->dispatch('test.event', $eventFired);

        // Then: Both listener receive the event with the same arguments
        $this->assertEquals(2, $received);
    }

    /**
     * @test
     */
    public function checkThatLaravelListenerCanStopEventPropagation()
    {
        // Given: A EventDispatcher with some listeners.
        $events = $this->events;
        $events->listen('test.event', function () {
            return 1;
        }, -10);
        $events->listen('test.event', function () {
            return 2;
        }, 0);
        $events->listen('test.event', function () {
            return 3;
        }, 10);

        // When: A Listener that stop propagation is in the chain
        $events->listen('test.event', function () {
            return false;
        }, -5);

        // Then: When the event is fired the propagation is stopped
        $result = $events->fire('test.event');
        $this->assertCount(2, $result);
        $this->assertEquals([3, 2], $result);
    }

    /**
     * @test
     */
    public function checkThatLaravelFireUntilStopOnNull()
    {
        // Given: A EventDispatcher with some listeners.
        $events = $this->events;
        $fired = [];
        $events->listen('test.event', function () use (&$fired) {
            $fired[] = 1;
        }, -10);
        $events->listen('test.event', function () use (&$fired) {
            $fired[] = 2;
        }, 0);
        $events->listen('test.event', function () use (&$fired) {
            $fired[] = 3;
        }, 10);

        // When: A Listener that return non-null is in the chain
        $events->listen('test.event', function () {
            return true;
        }, -5);

        // Then: When the event is fired with halt the propagation is stopped
        $events->until('test.event');
        $this->assertCount(2, $fired);
        $this->assertEquals([3, 2], $fired);
    }

    /**
     * @test
     */
    public function checkThatLaravelShouldBroadcastEventIsBroadcasted()
    {
        // Given: A EventDispatcher using a container with a configured broadcaster.
        $container = new Container();
        $event = new TestBroadcastEvent();
        $broadcaster = $this->getMockBuilder(BroadcastManager::class)
            ->setConstructorArgs([$container])
            ->getMock();
        $broadcaster->expects($this->once())->method('queue')->with($event);

        $container[BroadcastingFactory::class] = $broadcaster;
        $events = new EventDispatcher($container);

        // When: A ShouldBroadcast Event is fired
        $events->fire($event);

        // Then: A Broadcast Event is sent
    }
}

class TestBroadcastEvent implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return ['test-channel'];
    }
}
