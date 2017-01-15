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

namespace TTIC\Laravel\Bridge\Container;

use Exception;
use Illuminate\Container\Container as LaravelContainer;
use Interop\Container\ContainerInterface;
use TTIC\Interop\Container\DelegateContainerTrait;
use TTIC\Interop\Container\Exception\ContainerException;


/**
 * Extends a Laravel container to implement ContainerInterface and
 * delegate container resolution.
 *
 * @package TTIC\Container
 * @author Marcos Lois Bermúdez <marcos.lois@teratic.com>
 */
class Container extends LaravelContainer implements ContainerInterface
{
    use DelegateContainerTrait;

    /**
     * {@inheritDoc}
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($this->normalize($abstract));

        if (!$this->bound($abstract) && $this->delegateHas($abstract)) {
            return $this->delegateGet($abstract);
        }

        return parent::make($abstract, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function has($id)
    {
        return $this->bound($id);
    }

    /**
     * {@inheritDoc}
     */
    public function get($id)
    {
        try {
            return $this->make($id);
        } catch(Exception $e) {
            throw ContainerException::fromPrevious($id, $e);
        }
    }
}
