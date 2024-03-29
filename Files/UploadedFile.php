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

use BlitzPHP\Container\Services;
use BlitzPHP\Filesystem\Exceptions\FileNotFoundException;
use BlitzPHP\Filesystem\FilesystemManager;
use BlitzPHP\Traits\Macroable;
use BlitzPHP\Utilities\Iterable\Arr;
use GuzzleHttp\Psr7\UploadedFile as GuzzleUploadedFile;

/**
 * @credit <a href="http://laravel.com">Laravel - \Illuminate\Http\UploadedFile</a>
 */
class UploadedFile extends GuzzleUploadedFile
{
    use FileHelpers;
    use Macroable;

    private ?FilesystemManager $filesystemManager = null;

    /**
     * Stockez le fichier téléchargé sur un disque de système de fichiers.
     *
     * @return false|string
     */
    public function store(string $path, array|string $options = [])
    {
        return $this->storeAs($path, $this->hashName(), $this->parseOptions($options));
    }

    /**
     * Stockez le fichier téléchargé sur un disque de système de fichiers avec une visibilité publique.
     *
     * @return false|string
     */
    public function storePublicly(string $path, array|string $options = [])
    {
        return $this->storePubliclyAs($path, $this->hashName(), $options);
    }

    /**
     * Stockez le fichier téléchargé sur un disque de système de fichiers avec une visibilité publique.
     *
     * @return false|string
     */
    public function storePubliclyAs(string $path, string $name, array|string $options = [])
    {
        $options = $this->parseOptions($options);

        $options['visibility'] = 'public';

        return $this->storeAs($path, $name, $options);
    }

    /**
     * Stockez le fichier téléchargé sur un disque de système de fichiers.
     *
     * @return false|string
     */
    public function storeAs(string $path, string $name, array|string $options = [])
    {
        $options = $this->parseOptions($options);

        $disk = Arr::pull($options, 'disk');

        return $this->filesystemManager()->disk($disk)->putFileAs(
            $path,
            $this,
            $name,
            $options
        );
    }

    /**
     * Obtenez le contenu du fichier téléchargé.
     *
     * @return false|string
     *
     * @throws FileNotFoundException
     */
    public function get()
    {
        if (! $this->isValid()) {
            throw new FileNotFoundException("Le fichier n'existe pas dans le chemin {$this->getPathname()}.");
        }

        return file_get_contents($this->getPathname());
    }

    /**
     * Renvoie true s'il n'y a pas d'erreur de téléchargement
     */
    public function isValid(): bool
    {
        return $this->getError() === UPLOAD_ERR_OK;
    }

    /**
     * Obtenez l'extension du fichier fournie par le client.
     */
    public function clientExtension(): string
    {
        return pathinfo($this->getClientFilename(), PATHINFO_EXTENSION);
    }

    /**
     * Récupérez le type de média du fichier.
     */
    public function getMimeType(): string
    {
        if (null !== $type = $this->getClientMediaType()) {
            return $type;
        }

        return Mimes::guessTypeFromExtension($this->clientExtension()) ?? '';
    }

    public function getClientFilename(): string
    {
        return parent::getClientFilename() ?: pathinfo($this->getPathname(), PATHINFO_BASENAME);
    }

    public function getPath(): string
    {
        return $this->getPathname();
    }

    public function getPathname(): string
    {
        return $this->getStream()->getMetadata('uri');
    }

    /**
     * Créez une nouvelle instance de fichier à partir d'une instance de base.
     *
     * @return static
     */
    public static function createFromBase(GuzzleUploadedFile $file)
    {
        return $file instanceof static ? $file : new static(
            $file->getStream(),
            $file->getSize(),
            $file->getError(),
            $file->getClientFilename(),
            $file->getClientMediaType()
        );
    }

    /**
     * Analysez et formatez les options données.
     */
    protected function parseOptions(string|array $options): array
    {
        if (is_string($options)) {
            $options = ['disk' => $options];
        }

        return $options;
    }

    private function filesystemManager(): FilesystemManager
    {
        if (null !== $this->filesystemManager) {
            return $this->filesystemManager;
        }
        if (class_exists(Services::class)) {
            return $this->filesystemManager = Services::storage();
        }

        return $this->filesystemManager = new FilesystemManager([
            'default' => 'local',
            'disks'   => [
                'local' => [
                    'driver' => 'local',
                    'root'   => dirname(__DIR__),
                    'throw'  => false,
                ],
            ],
        ]);
    }
}
