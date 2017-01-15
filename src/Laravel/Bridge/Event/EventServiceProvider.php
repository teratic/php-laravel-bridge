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

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A Laravel service provider that register a event listener that is
 * compatible with Symfony EventDispatcherInterface, and a
 * EventDispatcherInterface alias to allow resolver to inject this type.
 *
 * @package TTIC\Laravel\Bridge\Event
 * @author Marcos Lois Bermúdez <marcos.lois@teratic.com>
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('events', function (Application $app) {
            return (new EventDispatcher($app))->setQueueResolver(function () use ($app) {
                return $app->make('Illuminate\Contracts\Queue\Factory');
            });
        });

        // Register a alias for the Symfony interface
        $this->app->alias(EventDispatcherInterface::class, 'events');
    }
}
