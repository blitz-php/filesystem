<?php

/**
 * This file is part of Blitz PHP framework - Filesystem.
 *
 * (c) 2023 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Filesystem\Exceptions;

use BlitzPHP\Traits\Support\Translatable;
use RuntimeException;

class FileNotFoundException extends RuntimeException
{
    use Translatable;

    public static function fileNotFound(string $path)
    {
        return new static(static::translate('Files.fileNotFound', [$path]));
    }
}
