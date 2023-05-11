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

/**
 * L'interface de cache de BlitzPHP
 */
interface FilesystemInterface
{
    /**
     * The public visibility setting.
     *
     * @var string
     */
    public const VISIBILITY_PUBLIC = 'public';

    /**
     * The private visibility setting.
     *
     * @var string
     */
    public const VISIBILITY_PRIVATE = 'private';

    /**
     * Determine if a file exists.
     */
    public function exists(string $path): bool;

    /**
     * Get the contents of a file.
     */
    public function get(string $path): ?string;

    /**
     * Get a resource to read the file.
     *
     * @return resource|null The path resource or null on failure.
     */
    public function readStream(string $path);

    /**
     * Write the contents of a file.
     *
     * @param resource|string $contents
     *
     * @return bool
     */
    public function put(string $path, $contents, mixed $options = []);

    /**
     * Write a new file using a stream.
     *
     * @param resource $resource
     *
     * @return bool
     */
    public function writeStream(string $path, $resource, array $options = []);

    /**
     * Get the visibility for the given path.
     */
    public function getVisibility(string $path): string;

    /**
     * Set the visibility for the given path.
     */
    public function setVisibility(string $path, string $visibility): bool;

    /**
     * Prepend to a file.
     */
    public function prepend(string $path, string $data): bool;

    /**
     * Append to a file.
     */
    public function append(string $path, string $data): bool;

    /**
     * Delete the file at a given path.
     */
    public function delete(string|array $paths): bool;

    /**
     * Copy a file to a new location.
     */
    public function copy(string $from, string $to): bool;

    /**
     * Move a file to a new location.
     */
    public function move(string $from, string $to): bool;

    /**
     * Get the file size of a given file.
     */
    public function size(string $path): int;

    /**
     * Get the file's last modification time.
     */
    public function lastModified(string $path): int;

    /**
     * Get an array of all files in a directory.
     */
    public function files(?string $directory = null, bool $recursive = false): array;

    /**
     * Get all of the files from the given directory (recursive).
     */
    public function allFiles(?string $directory = null): array;

    /**
     * Get all of the directories within a given directory.
     */
    public function directories(?string $directory = null, bool $recursive = false): array;

    /**
     * Get all (recursive) of the directories within a given directory.
     */
    public function allDirectories(?string $directory = null): array;

    /**
     * Create a directory.
     */
    public function makeDirectory(string $path): bool;

    /**
     * Recursively delete a directory.
     */
    public function deleteDirectory(string $directory): bool;
}
