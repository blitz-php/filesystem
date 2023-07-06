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

use BlitzPHP\Filesystem\Exceptions\FileException;
use BlitzPHP\Filesystem\Exceptions\FileNotFoundException;
use BlitzPHP\Utilities\Date;
use ReturnTypeWillChange;
use SplFileInfo;

/**
 * Wrapper pour le SplFileInfo intégré de PHP, avec des bonus.
 *
 * @credit <a href="http://codeigniter.com">CodeIgniter 4 - \CodeIgniter\Files\FileCollector</a>
 */
class File extends SplFileInfo
{
    /**
     * La taille des fichiers en octets
     *
     * @var int
     */
    protected $size;

    /**
     * @var string|null
     */
    protected $originalMimeType;

    /**
     * Exécutez notre constructeur SplFileInfo avec une vérification facultative que le chemin est bien un fichier.
     *
     * @throws FileNotFoundException
     */
    public function __construct(string $path, bool $checkFile = false)
    {
        if ($checkFile && ! is_file($path)) {
            throw FileNotFoundException::fileNotFound($path);
        }

        parent::__construct($path);
    }

    /**
     * Récupérez la taille du fichier.
     *
     * Les implémentations DEVRAIENT renvoyer la valeur stockée dans la clé "size" du fichier dans le tableau
     * $_FILES si disponible, car PHP calcule cela en fonction de la taille réelle transmise.
     *
     * @return false|int La taille du fichier en octets, ou false en cas d'échec
     */
    #[ReturnTypeWillChange]
    public function getSize()
    {
        return $this->size ?? ($this->size = parent::getSize());
    }

    /**
     * Récupérer la taille du fichier par unité.
     *
     * @return false|int|string
     */
    public function getSizeByUnit(string $unit = 'b')
    {
        return match (strtolower($unit)) {
            'kb'    => number_format($this->getSize() / 1024, 3),
            'mb'    => number_format(($this->getSize() / 1024) / 1024, 3),
            default => $this->getSize(),
        };
    }

    /**
     * Tente de déterminer l'extension de fichier en fonction de la méthode approuvée getType().
     * Si le type mime est inconnu, renverra null.
     */
    public function guessExtension(): ?string
    {
        // obtenir naïvement l'extension de chemin en utilisant pathinfo
        $pathinfo = pathinfo($this->getRealPath() ?: $this->__toString()) + ['extension' => ''];

        $proposedExtension = $pathinfo['extension'];

        return Mimes::guessExtensionFromType($this->getMimeType(), $proposedExtension);
    }

    /**
     * Récupérez le type de média du fichier.
     * NE DEVRAIT PAS utiliser les informations du tableau $_FILES,
     * mais devrait utiliser d'autres méthodes pour déterminer plus précisément le type de fichier, comme finfo,
     * ou mime_content_type().
     *
     * @return string Le type de média que nous avons déterminé.
     */
    public function getMimeType(): string
    {
        if (! function_exists('finfo_open')) {
            return $this->originalMimeType ?? 'application/octet-stream'; // @codeCoverageIgnore
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $this->getRealPath() ?: $this->__toString());
        finfo_close($finfo);

        return $mimeType;
    }

    /**
     * Génère des noms aléatoires basés sur un simple hachage et l'heure,
     * avec l'extension de fichier correcte jointe.
     */
    public function getRandomName(): string
    {
        $extension = $this->getExtension();
        $extension = empty($extension) ? '' : '.' . $extension;

        return Date::now()->getTimestamp() . '_' . bin2hex(random_bytes(10)) . $extension;
    }

    /**
     * Déplace un fichier vers un nouvel emplacement.
     *
     * @return File
     */
    public function move(string $targetPath, ?string $name = null, bool $overwrite = false)
    {
        $targetPath = rtrim($targetPath, '/') . '/';
        $name ??= $this->getBaseName();
        $destination = $overwrite ? $targetPath . $name : $this->getDestination($targetPath . $name);

        $oldName = $this->getRealPath() ?: $this->__toString();

        if (! @rename($oldName, $destination)) {
            $error = error_get_last();

            throw FileException::unableToMove($this->getBasename(), $targetPath, strip_tags($error['message']));
        }

        @chmod($destination, 0777 & ~umask());

        return new self($destination);
    }

    /**
     * Renvoie le chemin de destination pour l'opération de déplacement où l'écrasement n'est pas attendu.
     *
     * Premièrement, il vérifie si le délimiteur est présent dans le nom du fichier,
     * si c'est le cas, il vérifie si le dernier élément est un entier car il peut y avoir des cas où le délimiteur peut être présent dans le nom de fichier.
     * Pour tous les autres cas, il ajoute un entier commençant à zéro avant l'extension du fichier.
     */
    public function getDestination(string $destination, string $delimiter = '_', int $i = 0): string
    {
        if ($delimiter === '') {
            $delimiter = '_';
        }

        while (is_file($destination)) {
            $info      = pathinfo($destination);
            $extension = isset($info['extension']) ? '.' . $info['extension'] : '';

            if (strpos($info['filename'], $delimiter) !== false) {
                $parts = explode($delimiter, $info['filename']);

                if (is_numeric(end($parts))) {
                    $i = end($parts);
                    array_pop($parts);
                    $parts[]     = ++$i;
                    $destination = $info['dirname'] . DIRECTORY_SEPARATOR . implode($delimiter, $parts) . $extension;
                } else {
                    $destination = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . $delimiter . ++$i . $extension;
                }
            } else {
                $destination = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . $delimiter . ++$i . $extension;
            }
        }

        return $destination;
    }
}
