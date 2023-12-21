<?php

/**
 * This file is part of Blitz PHP framework - Filesystem.
 *
 * (c) 2023 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Filesystem\Adapters;

use BadMethodCallException;
use BlitzPHP\Contracts\Filesystem\FilesystemInterface;
use BlitzPHP\Filesystem\Files\File;
use BlitzPHP\Filesystem\Files\UploadedFile;
use BlitzPHP\Traits\Conditionable;
use BlitzPHP\Traits\Macroable;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\Iterable\Arr;
use BlitzPHP\Utilities\String\Text;
use Closure;
use DateTimeInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToProvideChecksum;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

/**
 * @credit <a href="http://laravel.com/">Laravel - Illuminate\Filesystem\FilesystemAdapter</a>
 */
class FilesystemAdapter implements FilesystemInterface
{
    use Conditionable;
    use Macroable {
        __call as macroCall;
    }

    /**
     * L'instance Flysystem PathPrefixer.
     *
     * @var \League\Flysystem\PathPrefixer
     */
    protected $prefixer;

    /**
     * Callback du générateur d'URL temporaire.
     *
     * @var Closure|null
     */
    protected $temporaryUrlCallback;

    /**
     * Créez une nouvelle instance d'adaptateur de système de fichiers.
     *
     * @param FilesystemOperator $driver  L'implémentation du système de fichiers Flysystem.
     * @param FlysystemAdapter   $adapter L'implémentation de l'adaptateur Flysystem.
     * @param array              $config  La configuration du système de fichiers.
     */
    public function __construct(protected FilesystemOperator $driver, protected FlysystemAdapter $adapter, protected array $config = [])
    {
        $separator = $config['directory_separator'] ?? DIRECTORY_SEPARATOR;

        $this->prefixer = new PathPrefixer($config['root'] ?? '', $separator);

        if (isset($config['prefix'])) {
            $this->prefixer = new PathPrefixer($this->prefixer->prefixPath($config['prefix']), $separator);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $path): bool
    {
        return $this->driver->has($path);
    }

    /**
     * Déterminez si un fichier ou un répertoire est manquant.
     */
    public function missing(string $path): bool
    {
        return ! $this->exists($path);
    }

    /**
     * Déterminez si un fichier existe.
     */
    public function fileExists(string $path): bool
    {
        return $this->driver->fileExists($path);
    }

    /**
     * Déterminez si un fichier est manquant.
     */
    public function fileMissing(string $path): bool
    {
        return ! $this->fileExists($path);
    }

    /**
     * Déterminez si un répertoire existe.
     */
    public function directoryExists(string $path): bool
    {
        return $this->driver->directoryExists($path);
    }

    /**
     * Déterminez si un répertoire est manquant.
     */
    public function directoryMissing(string $path): bool
    {
        return ! $this->directoryExists($path);
    }

    /**
     * Obtenez le chemin complet du fichier dans le chemin "court" donné.
     */
    public function path(string $path): string
    {
        return $this->prefixer->prefixPath($path);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $path): ?string
    {
        try {
            return $this->driver->read($path);
        } catch (UnableToReadFile $e) {
            Helpers::throwIf($this->throwsExceptions(), $e);
        }

        return null;
    }

    /**
     * Créez une réponse diffusée pour un fichier donné.
     */
    public function response(string $path, ?string $name = null, array $headers = [], ?string $disposition = 'inline'): Response
    {
        $response = new Response();

        if (! array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = $this->mimeType($path);
        }

        if (! array_key_exists('Content-Length', $headers)) {
            $headers['Content-Length'] = $this->size($path);
        }

        if (! array_key_exists('Content-Disposition', $headers)) {
            $filename = $name ?? basename($path);

            $headers['Content-Disposition'] = self::makeDisposition($disposition, $filename, $this->fallbackName($filename));
        }

        foreach ($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        return $response->withBody(Utils::streamFor($this->readStream($path)));
    }

    /**
     * Créez une réponse de téléchargement en continu pour un fichier donné.
     */
    public function download(string $path, ?string $name = null, array $headers = []): Response
    {
        return $this->response($path, $name, $headers, 'attachment');
    }

    /**
     * Convertissez la chaîne en caractères ASCII équivalents au nom donné.
     */
    protected function fallbackName(string $name): string
    {
        return str_replace('%', '', Text::ascii($name));
    }

    /**
     * {@inheritDoc}
     *
     * @param File|resource|StreamInterface|string|UploadedFile $contents
     *
     * @return bool|string
     */
    public function put(string $path, $contents, mixed $options = [])
    {
        $options = is_string($options)
                     ? ['visibility' => $options]
                     : (array) $options;

        // Si le contenu donné est en fait un fichier ou une instance de fichier téléchargé, nous stockerons automatiquement le fichier à l'aide d'un flux.
        // Cela fournit un chemin pratique au développeur pour stocker les flux sans les gérer manuellement dans le code.
        if ($contents instanceof File || $contents instanceof UploadedFile) {
            return $this->putFile($path, $contents, $options);
        }

        try {
            if ($contents instanceof StreamInterface) {
                $this->driver->writeStream($path, $contents->detach(), $options);

                return true;
            }

            is_resource($contents)
                ? $this->driver->writeStream($path, $contents, $options)
                : $this->driver->write($path, $contents, $options);
        } catch (UnableToWriteFile|UnableToSetVisibility $e) {
            Helpers::throwIf($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * Stockez le fichier téléchargé sur le disque.
     *
     * @param File|string|UploadedFile $file
     *
     * @return false|string
     */
    public function putFile(string $path, $file, mixed $options = [])
    {
        $file = is_string($file) ? new File($file) : $file;

        return $this->putFileAs($path, $file, $file->hashName(), $options);
    }

    /**
     * Stockez le fichier téléchargé sur le disque avec un nom donné.
     *
     * @param File|string|UploadedFile $file
     *
     * @return false|string
     */
    public function putFileAs(string $path, $file, string $name, mixed $options = [])
    {
        $stream = fopen(is_string($file) ? $file : $file->getRealPath(), 'rb');

        // Ensuite, nous allons formater le chemin du fichier et stocker le fichier à l'aide d'un flux car ils offrent de meilleures performances que les alternatives.
        // Une fois que nous aurons écrit le fichier, ce flux sera fermé automatiquement par nous afin que le développeur n'ait pas à le faire.
        $result = $this->put(
            $path = trim($path . '/' . $name, '/'),
            $stream,
            $options
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $result ? $path : false;
    }

    /**
     * {@inheritDoc}
     */
    public function getVisibility(string $path): string
    {
        if ($this->driver->visibility($path) === Visibility::PUBLIC) {
            return FilesystemInterface::VISIBILITY_PUBLIC;
        }

        return FilesystemInterface::VISIBILITY_PRIVATE;
    }

    /**
     * {@inheritDoc}
     */
    public function setVisibility(string $path, string $visibility): bool
    {
        try {
            $this->driver->setVisibility($path, $this->parseVisibility($visibility));
        } catch (UnableToSetVisibility $e) {
            Helpers::throwIf($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(string $path, string $data, string $separator = PHP_EOL): bool
    {
        if ($this->fileExists($path)) {
            return $this->put($path, $data . $separator . $this->get($path));
        }

        return $this->put($path, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function append(string $path, string $data, string $separator = PHP_EOL): bool
    {
        if ($this->fileExists($path)) {
            return $this->put($path, $this->get($path) . $separator . $data);
        }

        return $this->put($path, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string|array $paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                $this->driver->delete($path);
            } catch (UnableToDeleteFile $e) {
                Helpers::throwIf($this->throwsExceptions(), $e);

                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritDoc}
     */
    public function copy(string $from, string $to): bool
    {
        try {
            $this->driver->copy($from, $to);
        } catch (UnableToCopyFile $e) {
            Helpers::throwIf($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function move(string $from, string $to): bool
    {
        try {
            $this->driver->move($from, $to);
        } catch (UnableToMoveFile $e) {
            Helpers::throwIf($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function size(string $path): int
    {
        return $this->driver->fileSize($path);
    }

    /**
     * Obtenir la somme de contrôle d'un fichier.
     *
     * @return false|string
     *
     * @throws UnableToProvideChecksum
     */
    public function checksum(string $path, array $options = [])
    {
        try {
            return $this->driver->checksum($path, $options);
        } catch (UnableToProvideChecksum $e) {
            Helpers::throwIf($this->throwsExceptions(), $e);

            return false;
        }
    }

    /**
     * Récupère le type mime d'un fichier donné.
     *
     * @return false|string
     */
    public function mimeType(string $path)
    {
        try {
            return $this->driver->mimeType($path);
        } catch (UnableToRetrieveMetadata $e) {
            Helpers::throwIf($this->throwsExceptions(), $e);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function lastModified(string $path): int
    {
        return $this->driver->lastModified($path);
    }

    /**
     * {@inheritDoc}
     */
    public function readStream($path)
    {
        try {
            return $this->driver->readStream($path);
        } catch (UnableToReadFile $e) {
            Helpers::throwIf($this->throwsExceptions(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function writeStream($path, $resource, array $options = [])
    {
        try {
            $this->driver->writeStream($path, $resource, $options);
        } catch (UnableToWriteFile|UnableToSetVisibility $e) {
            Helpers::throwIf($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * Obtenez l'URL du fichier au chemin indiqué.
     *
     * @throws RuntimeException
     */
    public function url(string $path): string
    {
        if (isset($this->config['prefix'])) {
            $path = $this->concatPathToUrl($this->config['prefix'], $path);
        }

        $adapter = $this->adapter;

        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        }
        if (method_exists($this->driver, 'getUrl')) {
            return $this->driver->getUrl($path);
        }
        if ($adapter instanceof FtpAdapter || $adapter instanceof SftpAdapter) {
            return $this->getFtpUrl($path);
        }
        if ($adapter instanceof LocalAdapter) {
            return $this->getLocalUrl($path);
        }

        throw new RuntimeException('Ce pilote ne prend pas en charge la récupération des URL.');
    }

    /**
     * Obtenez l'URL du fichier au chemin indiqué.
     */
    protected function getFtpUrl(string $path): string
    {
        return isset($this->config['url'])
                ? $this->concatPathToUrl($this->config['url'], $path)
                : $path;
    }

    /**
     * Obtenez l'URL du fichier au chemin indiqué.
     */
    protected function getLocalUrl(string $path): string
    {
        // Si une URL de base explicite a été définie sur la configuration du disque, nous l'utiliserons comme URL de base au lieu du chemin par défaut.
        // Cela permet au développeur d'avoir un contrôle total sur le chemin de base des URL générées par ce système de fichiers.
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $path);
        }

        $path = '/storage/' . $path;

        // Si le chemin contient "stockage/public", cela signifie probablement que le développeur utilise le disque par défaut pour générer le chemin au lieu du disque "public" comme il est vraiment censé l'utiliser.
        // Nous allons supprimer le public de ce chemin ici.
        if (str_contains($path, '/storage/public/')) {
            return Text::replaceFirst('/public/', '/', $path);
        }

        return $path;
    }

    /**
     * Déterminez si des URL temporaires peuvent être générées.
     */
    public function providesTemporaryUrls(): bool
    {
        return method_exists($this->adapter, 'getTemporaryUrl') || isset($this->temporaryUrlCallback);
    }

    /**
     * Obtenez une URL temporaire pour le fichier au chemin donné.
     *
     * @throws RuntimeException
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        if (method_exists($this->adapter, 'getTemporaryUrl')) {
            return $this->adapter->getTemporaryUrl($path, $expiration, $options);
        }

        if ($this->temporaryUrlCallback) {
            return $this->temporaryUrlCallback->bindTo($this, static::class)(
                $path,
                $expiration,
                $options
            );
        }

        throw new RuntimeException('Ce pilote ne prend pas en charge la création d\'URL temporaires.');
    }

    /**
     * Concaténer un chemin vers une URL.
     */
    protected function concatPathToUrl(string $url, string $path): string
    {
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Remplacez le schéma, l'hôte et le port de l'UriInterface donnée par les valeurs de l'URL donnée.
     */
    protected function replaceBaseUrl(UriInterface $uri, string $url): UriInterface
    {
        $parsed = parse_url($url);

        return $uri
            ->withScheme($parsed['scheme'])
            ->withHost($parsed['host'])
            ->withPort($parsed['port'] ?? null);
    }

    /**
     * {@inheritDoc}
     */
    public function files(?string $directory = null, bool $recursive = false): array
    {
        return $this->driver->listContents($directory ?? '', $recursive)
            ->filter(fn (StorageAttributes $attributes) => $attributes->isFile())
            ->sortByPath()
            ->map(fn (StorageAttributes $attributes) => $attributes->path())
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function allFiles(?string $directory = null): array
    {
        return $this->files($directory, true);
    }

    /**
     * {@inheritDoc}
     */
    public function directories(?string $directory = null, bool $recursive = false): array
    {
        return $this->driver->listContents($directory ?? '', $recursive)
            ->filter(fn (StorageAttributes $attributes) => $attributes->isDir())
            ->map(fn (StorageAttributes $attributes) => $attributes->path())
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function allDirectories(?string $directory = null): array
    {
        return $this->directories($directory, true);
    }

    /**
     * {@inheritDoc}
     */
    public function makeDirectory(string $path): bool
    {
        try {
            $this->driver->createDirectory($path);
        } catch (UnableToCreateDirectory|UnableToSetVisibility $e) {
            Helpers::throwIf($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDirectory(string $directory): bool
    {
        try {
            $this->driver->deleteDirectory($directory);
        } catch (UnableToDeleteDirectory $e) {
            Helpers::throwIf($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * Obtenez le pilote Flysystem.
     */
    public function getDriver(): FilesystemOperator
    {
        return $this->driver;
    }

    /**
     * Obtenez l'adaptateur Flysystem.
     */
    public function getAdapter(): FlysystemAdapter
    {
        return $this->adapter;
    }

    /**
     * Obtenez les valeurs de configuration.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Analyser la valeur de visibilité donnée.
     *
     * @throws InvalidArgumentException
     */
    protected function parseVisibility(?string $visibility): ?string
    {
        if (null === $visibility) {
            return null;
        }

        return match ($visibility) {
            FilesystemInterface::VISIBILITY_PUBLIC  => Visibility::PUBLIC,
            FilesystemInterface::VISIBILITY_PRIVATE => Visibility::PRIVATE,
            default                                 => throw new InvalidArgumentException("Visibilité inconnue: {$visibility}."),
        };
    }

    /**
     * Définissez un rappel de générateur d'URL temporaire personnalisé.
     */
    public function buildTemporaryUrlsUsing(Closure $callback): void
    {
        $this->temporaryUrlCallback = $callback;
    }

    /**
     * Déterminez si les exceptions Flysystem doivent être levées.
     */
    protected function throwsExceptions(): bool
    {
        return (bool) ($this->config['throw'] ?? false);
    }

    /**
     * Passez l'appel de méthodes dynamiques à Flysystem.
     *
     * @throws BadMethodCallException
     */
    public function __call(string $method, array $parameters = []): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->driver->{$method}(...$parameters);
    }

    /**
     * Génère une valeur de champ HTTP Content-Disposition.
     *
     * @param string $disposition      Une valeur entre "inline" ou "attachment"
     * @param string $filename         Une chaîne unicode
     * @param string $filenameFallback Une chaîne contenant uniquement des caractères ASCII qui est sémantiquement équivalente à $filename.
     *                                 Si le nom de fichier est déjà ASCII, il peut être omis ou simplement copié à partir de $filename
     *
     * @throws InvalidArgumentException
     *
     * @see RFC 6266
     */
    public static function makeDisposition(string $disposition, string $filename, string $filenameFallback = ''): string
    {
        if (! \in_array($disposition, ['attachment', 'inline'], true)) {
            throw new InvalidArgumentException('La disposition doit être "attachment" ou "inline".');
        }

        if ('' === $filenameFallback) {
            $filenameFallback = $filename;
        }

        // filenameFallback n'est pas ASCII.
        if (! preg_match('/^[\x20-\x7e]*$/', $filenameFallback)) {
            throw new InvalidArgumentException('Le nom de fichier de secours ne doit contenir que des caractères ASCII.');
        }

        // Les caractères de pourcentage ne sont pas sûrs en mode de repli.
        if (str_contains($filenameFallback, '%')) {
            throw new InvalidArgumentException('Le nom de fichier de secours ne peut pas contenir le caractère "%".');
        }

        // les séparateurs de chemin ne sont pas autorisés non plus.
        if (str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filenameFallback, '/') || str_contains($filenameFallback, '\\')) {
            throw new InvalidArgumentException('Le nom de fichier et le fallback ne peuvent pas contenir les caractères "/" et "\\".');
        }

        $params = ['filename' => $filenameFallback];
        if ($filename !== $filenameFallback) {
            $params['filename*'] = "utf-8''" . rawurlencode($filename);
        }

        return $disposition . '; ' . Arr::toString($params, ';', true);
    }
}
