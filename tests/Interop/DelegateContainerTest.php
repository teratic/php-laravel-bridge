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

use Exception;
use PHPUnit_Framework_TestCase as TestCase;
use TTIC\Interop\Container\DelegateContainerTrait;
use TTIC\Interop\Container\Exception\ContainerException;
use TTIC\Interop\Pimple\Container;

/**
 * Delegate Container Tests to check that delegation to other
 * containers work as spected.
 *
 * @covers \TTIC\Interop\Container\DelegateContainerTrait
 * @covers \TTIC\Interop\Container\Exception\ContainerException
 */
class DelegateContainerTest extends TestCase
{
    public function testContainerException()
    {
        // Given: A exception
        $e = new Exception('A exception message');

        // When: A new ContainerException is created
        $ce = ContainerException::fromPrevious($e->getMessage(), $e);

        // Then: The original exception is wrapped and the error message
        // is present on ContainerException
        $this->assertEquals($e, $ce->getPrevious());
        $this->assertContains($e->getMessage(), $ce->getMessage());
    }

    public function testThatMissingEntryAreResolvedInDelegateContainer()
    {
        // Given: A Container that use DelegateContainerTrait and implements delegation
        $parent = $this->getMockForTrait(DelegateContainerTrait::class);

        // When: A container is added
        $cont = new Container(['other' => 'value']);
        $parent->addDelegateContainer($cont);

        // Then: The parent can resolve in delegated container
        // and the delegated container is on the list of delegates
        call_user_func(\Closure::bind(function (TestCase $unit) use ($parent, $cont) {
            // Then: The resource can be resolved
            $unit->assertTrue($parent->delegateHas('other'));
            $unit->assertFalse($parent->delegateHas('not-found'));
            $unit->assertEquals($cont->get('other'), $parent->delegateGet('other'));
            $unit->assertEquals($cont->get('other'), $parent->delegateGet('other'));
        }, null, $parent), $this);
        $this->assertContains($cont, $parent->getDelegateContainers());

    }

    /**
     * @expectedException \TTIC\Interop\Container\Exception\ContainerException
     */
    public function testThatMissingEntryThrowsContainerException()
    {
        // Given: A Container that use DelegateContainerTrait and implements delegation
        $container = $this->getMockForTrait(DelegateContainerTrait::class);

        // When: A missing entry is resolved
        call_user_func(\Closure::bind(function () use ($container) {
            $container->delegateGet('not-found');
        }, null, $container));

        // Then: A ContainerException is thrown
    }
}
