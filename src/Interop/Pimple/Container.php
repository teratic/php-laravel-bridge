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

namespace TTIC\Interop\Pimple;

use Interop\Container\ContainerInterface;
use Pimple\Container as PimpleContainer;
use TTIC\Interop\Container\DelegateContainerTrait;
use TTIC\Interop\Container\Exception\ContainerException;

/**
 * Extends a Pimple container to implement ContainerInterface and
 * delegate container resolution.
 *
 * @package TTIC\Interop\Pimple
 * @author Marcos Lois Bermúdez <marcos.lois@teratic.com>
 */
class Container extends PimpleContainer implements ContainerInterface
{
    use DelegateContainerTrait;

    /**
     * {@inheritDoc}
     */
    public function offsetGet($id)
    {
        if (!$this->offsetExists($id) && $this->delegateHas($id)) {
            return $this->delegateGet($id);
        }

        return parent::offsetGet($id);
    }

    /**
     * {@inheritDoc}
     */
    public function has($id)
    {
        return $this->offsetExists($id);
    }

    /**
     * {@inheritDoc}
     */
    public function get($id)
    {
        if (!$this->offsetExists($id) && !$this->delegateHas($id)) {
            throw new ContainerException(sprintf('Identifier "%s" is not defined.', $id));
        }

        return $this->offsetGet($id);
    }
}
