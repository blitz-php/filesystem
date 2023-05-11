<?php

/**
 * This file is part of Blitz PHP framework - Filesystem.
 *
 * (c) 2023 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Filesystem;

use BadMethodCallException;
use BlitzPHP\Traits\Conditionable;
use BlitzPHP\Traits\Macroable;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\String\Text;
use Closure;
use DateTimeInterface;
use GuzzleHttp\Psr7\UploadedFile;
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
     * The Flysystem PathPrefixer instance.
     *
     * @var \League\Flysystem\PathPrefixer
     */
    protected $prefixer;

    /**
     * The temporary URL builder callback.
     *
     * @var Closure|null
     */
    protected $temporaryUrlCallback;

    /**
     * Create a new filesystem adapter instance.
     *
     * @param FilesystemOperator $driver  The Flysystem filesystem implementation.
     * @param FlysystemAdapter   $adapter The Flysystem adapter implementation.
     * @param array              $config  The filesystem configuration.
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
     * Determine if a file or directory is missing.
     */
    public function missing(string $path): bool
    {
        return ! $this->exists($path);
    }

    /**
     * Determine if a file exists.
     */
    public function fileExists(string $path): bool
    {
        return $this->driver->fileExists($path);
    }

    /**
     * Determine if a file is missing.
     */
    public function fileMissing(string $path): bool
    {
        return ! $this->fileExists($path);
    }

    /**
     * Determine if a directory exists.
     */
    public function directoryExists(string $path): bool
    {
        return $this->driver->directoryExists($path);
    }

    /**
     * Determine if a directory is missing.
     */
    public function directoryMissing(string $path): bool
    {
        return ! $this->directoryExists($path);
    }

    /**
     * Get the full path for the file at the given "short" path.
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
    }

    /**
     * Create a streamed response for a given file.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function response(string $path, ?string $name = null, array $headers = [], ?string $disposition = 'inline')
    {
        $response = new \Symfony\Component\HttpFoundation\StreamedResponse();

        if (! array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = $this->mimeType($path);
        }

        if (! array_key_exists('Content-Length', $headers)) {
            $headers['Content-Length'] = $this->size($path);
        }

        if (! array_key_exists('Content-Disposition', $headers)) {
            $filename = $name ?? basename($path);

            $disposition = $response->headers->makeDisposition(
                $disposition,
                $filename,
                $this->fallbackName($filename)
            );

            $headers['Content-Disposition'] = $disposition;
        }

        $response->headers->replace($headers);

        $response->setCallback(function () use ($path) {
            $stream = $this->readStream($path);
            fpassthru($stream);
            fclose($stream);
        });

        return $response;
    }

    /**
     * Create a streamed download response for a given file.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(string $path, ?string $name = null, array $headers = [])
    {
        return $this->response($path, $name, $headers, 'attachment');
    }

    /**
     * Convert the string to ASCII characters that are equivalent to the given name.
     */
    protected function fallbackName(string $name): string
    {
        return str_replace('%', '', Text::ascii($name));
    }

    /**
     * {@inheritDoc}
     *
     * @param \Illuminate\Http\File|resource|StreamInterface|string|UploadedFile $contents
     *
     * @return bool|string
     */
    public function put(string $path, $contents, mixed $options = [])
    {
        $options = is_string($options)
                     ? ['visibility' => $options]
                     : (array) $options;

        // If the given contents is actually a file or uploaded file instance than we will
        // automatically store the file using a stream. This provides a convenient path
        // for the developer to store streams without managing them manually in code.
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
     * Store the uploaded file on the disk.
     *
     * @param \Illuminate\Http\File|string|UploadedFile $file
     *
     * @return false|string
     */
    public function putFile(string $path, $file, mixed $options = [])
    {
        $file = is_string($file) ? new File($file) : $file;

        return $this->putFileAs($path, $file, $file->hashName(), $options);
    }

    /**
     * Store the uploaded file on the disk with a given name.
     *
     * @param \Illuminate\Http\File|string|UploadedFile $file
     *
     * @return false|string
     */
    public function putFileAs(string $path, $file, string $name, mixed $options = [])
    {
        $stream = fopen(is_string($file) ? $file : $file->getRealPath(), 'rb');

        // Next, we will format the path of the file and store the file using a stream since
        // they provide better performance than alternatives. Once we write the file this
        // stream will get closed automatically by us so the developer doesn't have to.
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
     * Get the checksum for a file.
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
     * Get the mime-type of a given file.
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
     * Get the URL for the file at the given path.
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

        throw new RuntimeException('This driver does not support retrieving URLs.');
    }

    /**
     * Get the URL for the file at the given path.
     */
    protected function getFtpUrl(string $path): string
    {
        return isset($this->config['url'])
                ? $this->concatPathToUrl($this->config['url'], $path)
                : $path;
    }

    /**
     * Get the URL for the file at the given path.
     */
    protected function getLocalUrl(string $path): string
    {
        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $path);
        }

        $path = '/storage/' . $path;

        // If the path contains "storage/public", it probably means the developer is using
        // the default disk to generate the path instead of the "public" disk like they
        // are really supposed to use. We will remove the public from this path here.
        if (str_contains($path, '/storage/public/')) {
            return Text::replaceFirst('/public/', '/', $path);
        }

        return $path;
    }

    /**
     * Determine if temporary URLs can be generated.
     */
    public function providesTemporaryUrls(): bool
    {
        return method_exists($this->adapter, 'getTemporaryUrl') || isset($this->temporaryUrlCallback);
    }

    /**
     * Get a temporary URL for the file at the given path.
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

        throw new RuntimeException('This driver does not support creating temporary URLs.');
    }

    /**
     * Concatenate a path to a URL.
     */
    protected function concatPathToUrl(string $url, string $path): string
    {
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Replace the scheme, host and port of the given UriInterface with values from the given URL.
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
     * Get the Flysystem driver.
     */
    public function getDriver(): FilesystemOperator
    {
        return $this->driver;
    }

    /**
     * Get the Flysystem adapter.
     */
    public function getAdapter(): FlysystemAdapter
    {
        return $this->adapter;
    }

    /**
     * Get the configuration values.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Parse the given visibility value.
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
            default                                 => throw new InvalidArgumentException("Unknown visibility: {$visibility}."),
        };
    }

    /**
     * Define a custom temporary URL builder callback.
     */
    public function buildTemporaryUrlsUsing(Closure $callback): void
    {
        $this->temporaryUrlCallback = $callback;
    }

    /**
     * Determine if Flysystem exceptions should be thrown.
     */
    protected function throwsExceptions(): bool
    {
        return (bool) ($this->config['throw'] ?? false);
    }

    /**
     * Pass dynamic methods call onto Flysystem.
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
}
