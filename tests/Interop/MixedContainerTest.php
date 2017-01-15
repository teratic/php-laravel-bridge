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

namespace TTIC\Test\Interop\Container;

use PHPUnit_Framework_TestCase as TestCase;
use TTIC\Interop\Pimple\Container as PimpleContainer;
use TTIC\Laravel\Bridge\Container\Container as LaravelContainer;

/**
 * Mixed Container Tests to ensure that Container implements
 * ContainerInterface and support delegation to other containers.
 *
 * @covers \TTIC\Laravel\Bridge\Container\Container
 * @covers \TTIC\Interop\Pimple\Container
 */
class MixedContainerTest extends TestCase
{
    /**
     * @test
     */
    public function checkResolveDelegation() {
        // Given: Two containers
        $cont1 = new LaravelContainer();
        $cont2 = new PimpleContainer();

        // When: Different elements are registered and one delegates
        // on another
        $cont1['one'] = 'Laravel One';
        $cont1['both'] = 'Laravel Both';
        $cont2['two'] = 'Pimple Two';
        $cont2['both'] = 'Pimple Both';
        $cont1->addDelegateContainer($cont2);

        // Then: Can resolve elements on delegated container.
        $this->assertTrue($cont1->has('one'));
        $this->assertFalse($cont1->has('two'));
        $this->assertTrue($cont1->has('both'));
        $this->assertFalse($cont2->has('one'));
        $this->assertTrue($cont2->has('two'));
        $this->assertTrue($cont2->has('both'));
        $this->assertEquals('Laravel One', $cont1->get('one'));
        $this->assertEquals('Pimple Two', $cont1->get('two'));
        $this->assertEquals('Laravel Both', $cont1->get('both'));
        $this->assertEquals('Pimple Two', $cont2->get('two'));
        $this->assertEquals('Pimple Both', $cont2->get('both'));
    }

    /**
     * @test
     */
    public function checkBothDelegationResolveOnBothContainers() {
        // Given: Two containers
        $cont1 = new LaravelContainer();
        $cont2 = new PimpleContainer();

        // When: Different elements are registered and one delegates
        // on another
        $cont1['one'] = 'Laravel One';
        $cont1['both'] = 'Laravel Both';
        $cont2['two'] = 'Pimple Two';
        $cont2['both'] = 'Pimple Both';
        $cont1->addDelegateContainer($cont2);
        $cont2->addDelegateContainer($cont1);

        // Then: Can resolve elements on delegated container.
        $this->assertTrue($cont1->has('one'));
        $this->assertFalse($cont1->has('two'));
        $this->assertTrue($cont1->has('both'));
        $this->assertFalse($cont2->has('one'));
        $this->assertTrue($cont2->has('two'));
        $this->assertTrue($cont2->has('both'));
        $this->assertEquals('Laravel One', $cont1->get('one'));
        $this->assertEquals('Laravel One', $cont2->get('one'));
        $this->assertEquals('Pimple Two', $cont1->get('two'));
        $this->assertEquals('Pimple Two', $cont2->get('two'));
        $this->assertEquals('Laravel Both', $cont1->get('both'));
        $this->assertEquals('Pimple Both', $cont2->get('both'));
    }

    /**
     * @test
     * @expectedException \TTIC\Interop\Container\Exception\ContainerException
     */
    public function ensureLaravelResolutionExceptionsAreWrapped() {
        $cont = new LaravelContainer();
        $cont->get('not-found');
    }

    /**
     * @test
     * @expectedException \TTIC\Interop\Container\Exception\ContainerException
     */
    public function ensurePimpleResolutionExceptionsAreWrapped() {
        $cont = new PimpleContainer();
        $cont->get('not-found');
    }
}
