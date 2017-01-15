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

namespace TTIC\Interop\Container;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\NotFoundException;
use TTIC\Interop\Container\Exception\ContainerException;

/**
 * Utility trait to add delegate container feature.
 *
 * @package TTIC\Container
 * @author Marcos Lois Bermúdez <marcos.lois@teratic.com>
 */
trait DelegateContainerTrait
{
    /**
     * @var ContainerInterface[]
     */
    protected $delegateContainers = [];

    /**
     * @param ContainerInterface $container
     */
    public function addDelegateContainer(ContainerInterface $container)
    {
        $this->delegateContainers[] = $container;
    }

    /**
     * @return ContainerInterface[]
     */
    public function getDelegateContainers()
    {
        return $this->delegateContainers;
    }

    /**
     * Check if any delegated containers has a entry.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool TRUE if any delegated container has the key, FALSE otherwise.
     */
    protected function delegateHas($id)
    {
        foreach ($this->delegateContainers as $container) {
            if ($container->has($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Finds an entry in the delegated container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for this identifier.
     * @throws ContainerException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    protected function delegateGet($id)
    {
        foreach ($this->delegateContainers as $container) {
            if ($container->has($id)) {
                return $container->get($id);
            }
        }

        throw ContainerException::fromPrevious($id, null);
    }
}
