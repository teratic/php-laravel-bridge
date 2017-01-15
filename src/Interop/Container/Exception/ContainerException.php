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

namespace TTIC\Interop\Container\Exception;

use Exception;
use Interop\Container\Exception\ContainerException as InteropContainerException;
use RuntimeException;

/**
 * An error occurred when trying to retrieve an entry from the container.
 *
 * @author Marcos Lois Bermúdez <marcos.lois@teratic.com>
 */
class ContainerException extends RuntimeException implements InteropContainerException
{
    /**
     * @var string The message template. Allowed variables are {error} and {id}
     */
    protected static $template = 'An {error} occurred when attempting to retrieve the "{id}" entry from the container.';

    /**
     * Creates a ContainerException by using the information from the previous exception
     *
     * @param string|mixed $id
     * @param \Exception $prev
     * @param int $code
     * @return \TTIC\Interop\Container\Exception\ContainerException
     */
    public static function fromPrevious($id, Exception $prev = null, $code = 0)
    {
        $message = strtr(static::$template, array(
            '{id}'    => (is_string($id) || method_exists($id, '__toString')) ? $id : '?',
            '{error}' => $prev ? get_class($prev) : 'error',
        ));

        if ($prev) {
            $message .= ' Message: ' . $prev->getMessage();
        }

        return new static($message, $code, $prev);
    }
}
