<?php

/**
 * This file is part of Blitz PHP framework - Filesystem.
 *
 * (c) 2023 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Filesystem\Files;

use BlitzPHP\Utilities\Date;

/**
 * @credit <a href="http://laravel.com">Laravel - \Illuminate\Http\FileHelpers</a>
 */
trait FileHelpers
{
    /**
     * La copie en cache du nom de hachage du fichier.
     */
    protected ?string $hashName = null;

    /**
     * Obtenez le chemin d'accès complet au fichier.
     */
    public function path(): string
    {
        return $this->getPathname();
    }

    /**
     * Obtenez l'extension du fichier.
     */
    public function extension(): string
    {
        return $this->clientExtension();
    }

    /**
     * Retourne l'extension basée sur le type de mime.
     *
     * Si le type de mime est inconnu, il renvoie null.
     *
     * Cette méthode utilise le type mime tel que deviné par getMimeType() pour deviner l'extension du fichier.
     */
    public function guessExtension(): ?string
    {
        return Mimes::guessExtensionFromType($this->getMimeType());
    }

    /**
     * Génère des noms aléatoires basés sur un simple hachage et l'heure,
     * avec l'extension de fichier correcte jointe.
     */
    public function hashName(?string $path = null): string
    {
        if ($path) {
            $path = rtrim($path, '/') . '/';
        }

        $hash = $this->hashName ?: $this->hashName = bin2hex(random_bytes(10));

        if (! empty($extension = $this->clientExtension())) {
            $extension = '.' . $extension;
        }

        return $path . Date::now()->getTimestamp() . '_' . $hash . $extension;
    }
}
