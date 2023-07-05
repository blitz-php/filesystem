<?php

namespace BlitzPHP\Filesystem\Exceptions;

use RuntimeException;

class FileException extends RuntimeException
{
    public static function unableToMove(?string $from = null, ?string $to = null, ?string $error = null)
    {
        return new static(lang('Files.cannotMove', [$from, $to, $error]));
    }

    /**
     * Lève lorsqu'un élément est censé être un répertoire mais qu'il ne l'est pas ou qu'il est manquant.
     *
     * @param string $caller La méthode à l'origine de l'exception
     */
    public static function expectedDirectory(string $caller)
    {
        return new static(lang('Files.expectedDirectory', [$caller]));
    }

    /**
     * Lève lorsqu'un élément est censé être un fichier mais qu'il ne l'est pas ou qu'il est manquant.
     *
     * @param string $caller La méthode provoquant l'exception
     */
    public static function expectedFile(string $caller)
    {
        return new static(lang('Files.expectedFile', [$caller]));
    }
}
