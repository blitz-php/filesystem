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

use BlitzPHP\Traits\Conditionable;
use BlitzPHP\Traits\Macroable;
use BlitzPHP\Utilities\Iterable\LazyCollection;
use ErrorException;
use FilesystemIterator;
use SplFileObject;
use Symfony\Component\Finder\Finder;

/**
 * @credit <a href="http://laravel.com/">Laravel - Illuminate\Filesystem\Filesystem</a>
 */
class Filesystem
{
    use Conditionable;
    use Macroable;

    /**
     * Déterminez si un fichier ou un répertoire existe.
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Determine if a file or directory is missing.
     */
    public function missing(string $path): bool
    {
        return ! $this->exists($path);
    }

    /**
     * Obtenir le contenu d'un fichier.
     *
     * @throws FileNotFoundException
     */
    public function get(string $path, bool $lock = false): string
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Obtenir le contenu d'un fichier avec accès partagé.
     */
    public function sharedGet(string $path): string
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $contents = fread($handle, $this->size($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }

    /**
     * Obtenir la valeur renvoyée d'un fichier.
     *
     * @throws FileNotFoundException
     */
    public function getRequire(string $path, array $data = []): mixed
    {
        if ($this->isFile($path)) {
            $__path = $path;
            $__data = $data;

            return (static function () use ($__path, $__data) {
                extract($__data, EXTR_SKIP);

                return require $__path;
            })();
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Exiger le fichier donné une fois.
     *
     * @throws FileNotFoundException
     */
    public function requireOnce(string $path, array $data = []): mixed
    {
        if ($this->isFile($path)) {
            $__path = $path;
            $__data = $data;

            return (static function () use ($__path, $__data) {
                extract($__data, EXTR_SKIP);

                return require_once $__path;
            })();
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Get the contents of a file one line at a time.
     *
     * @throws FileNotFoundException
     */
    public function lines(string $path): LazyCollection
    {
        if (! $this->isFile($path)) {
            throw new FileNotFoundException("File does not exist at path {$path}.");
        }

        return LazyCollection::make(function () use ($path) {
            $file = new SplFileObject($path);

            $file->setFlags(SplFileObject::DROP_NEW_LINE);

            while (! $file->eof()) {
                yield $file->fgets();
            }
        });
    }

    /**
     * Obtenir le hachage MD5 du fichier au chemin donné.
     */
    public function hash(string $path, string $algorithm = 'md5'): string
    {
        return hash_file($algorithm, $path);
    }

    /**
     * Écrire le contenu d'un fichier.
     *
     * @return false|int
     */
    public function put(string $path, string $contents, bool $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    /**
     * Écrire le contenu d'un fichier, en le remplaçant de manière atomique s'il existe déjà.
     */
    public function replace(string $path, string $content, ?int $mode = null): void
    {
        // If the path already exists and is a symlink, get the real path...
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        if (null !== $mode) {
            chmod($tempPath, $mode);
        } else {
            chmod($tempPath, 0777 - umask());
        }

        file_put_contents($tempPath, $content);

        rename($tempPath, $path);
    }

    /**
     * Replace a given string within a given file.
     */
    public function replaceInFile(array|string $search, array|string $replace, string $path): void
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    /**
     * Prepend to a file.
     *
     * @return false|int
     */
    public function prepend(string $path, string $data)
    {
        if ($this->exists($path)) {
            return $this->put($path, $data . $this->get($path));
        }

        return $this->put($path, $data);
    }

    /**
     * Append to a file.
     *
     * @return false|int
     */
    public function append(string $path, string $data)
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }

    /**
     * Get or set UNIX mode of a file or directory.
     */
    public function chmod(string $path, ?int $mode = null): mixed
    {
        if ($mode) {
            return chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    /**
     * Delete the file at a given path.
     */
    public function delete(string|array $paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                if (@unlink($path)) {
                    clearstatcache(false, $path);
                } else {
                    $success = false;
                }
            } catch (ErrorException $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Move a file to a new location.
     */
    public function move(string $path, string $target, bool $overwrite = true): bool
    {
        if (! is_file($target) || ($overwrite && is_file($target))) {
            return rename($path, $target);
        }

        return false;
    }

    /**
     * Copy a file to a new location.
     */
    public function copy(string $path, string $target, bool $overwrite = true): bool
    {
        if (! is_file($target) || ($overwrite && is_file($target))) {
            return copy($path, $target);
        }

        return false;
    }

    /**
     * Create a symlink to the target file or directory. On Windows, a hard link is created if the target is a file.
     *
     * @return bool|void
     */
    public function link(string $target, string $link)
    {
        if (! is_windows()) {
            return symlink($target, $link);
        }

        $mode = $this->isDirectory($target) ? 'J' : 'H';

        exec("mklink /{$mode} " . escapeshellarg($link) . ' ' . escapeshellarg($target));
    }

    /**
     * Extract the file name from a file path.
     */
    public function name(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path.
     */
    public function basename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path.
     */
    public function dirname(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Extract the file extension from a file path.
     */
    public function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the file type of a given file.
     */
    public function type(string $path): string
    {
        return filetype($path);
    }

    /**
     * Get the mime-type of a given file.
     *
     * @return false|string
     */
    public function mimeType(string $path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Get the file size of a given file.
     */
    public function size(string $path): int
    {
        return filesize($path);
    }

    /**
     * Get the file's last modification time.
     */
    public function lastModified(string $path): int
    {
        return filemtime($path);
    }

    /**
     * Determine if the given path is a directory.
     */
    public function isDirectory(string $directory): bool
    {
        return is_dir($directory);
    }

    /**
     * Determine if the given path is a directory that does not contain any other files or directories.
     */
    public function isEmptyDirectory(string $directory, bool $ignoreDotFiles = false): bool
    {
        return ! Finder::create()->ignoreDotFiles($ignoreDotFiles)->in($directory)->depth(0)->hasResults();
    }

    /**
     * Determine if the given path is readable.
     */
    public function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    /**
     * Determine if the given path is writable.
     */
    public function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    /**
     * Determine if two files are the same by comparing their hashes.
     */
    public function hasSameHash(string $firstFile, string $secondFile): bool
    {
        $hash = @md5_file($firstFile);

        return $hash && $hash === @md5_file($secondFile);
    }

    /**
     * Determine if the given path is a file.
     */
    public function isFile(string $file): bool
    {
        return is_file($file);
    }

    /**
     * Find path names matching a given pattern.
     *
     * @return array
     */
    public function glob(string $pattern, int $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * Get an array of all files in a directory.
     *
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    public function files(string $directory, bool $hidden = false, string $sortBy = 'name'): array
    {
        $files = Finder::create()->files()->ignoreDotFiles(! $hidden)->in($directory)->depth(0);

        switch (strtolower($sortBy)) {
            case 'type':
                $files = $files->sortByType();
                break;

            case 'modifiedtime':
            case 'modified':
                $files = $files->sortByModifiedTime();
                break;

            case 'changedtime':
            case 'changed':
                $files = $files->sortByChangedTime();
                break;

            case 'accessedtime':
            case 'accessed':
                $files = $files->sortByAccessedTime();
                break;

            default:
                $files = $files->sortByName();
                break;
        }

        return iterator_to_array($files, false);
    }

    /**
     * Get all of the files from the given directory (recursive).
     *
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    public function allFiles(string $directory, bool $hidden = false, string $sortBy = 'name'): array
    {
        $files = Finder::create()->files()->ignoreDotFiles(! $hidden)->in($directory);

        switch (strtolower($sortBy)) {
            case 'type':
                $files = $files->sortByType();
                break;

            case 'modifiedtime':
            case 'modified':
                $files = $files->sortByModifiedTime();
                break;

            case 'changedtime':
            case 'changed':
                $files = $files->sortByChangedTime();
                break;

            case 'accessedtime':
            case 'accessed':
                $files = $files->sortByAccessedTime();
                break;

            default:
                $files = $files->sortByName();
                break;
        }

        return iterator_to_array($files, false);
    }

    /**
     * Get all of the directories within a given directory.
     */
    public function directories(string $directory, int $depth = 0, bool $hidden = false): array
    {
        $directories = [];

        foreach (Finder::create()->ignoreDotFiles(! $hidden)->in($directory)->directories()->depth($depth)->sortByName() as $dir) {
            $directories[] = $dir->getPathname();
        }

        return $directories;
    }

    /**
     * Ensure a directory exists.
     */
    public function ensureDirectoryExists(string $path, int $mode = 0755, bool $recursive = true): void
    {
        if (! $this->isDirectory($path)) {
            $this->makeDirectory($path, $mode, $recursive);
        }
    }

    /**
     * Create a directory.
     */
    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false): bool
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Move a directory.
     */
    public function moveDirectory(string $from, string $to, bool $overwrite = false): bool
    {
        if ($overwrite && $this->isDirectory($to) && ! $this->deleteDirectory($to)) {
            return false;
        }

        return @rename($from, $to) === true;
    }

    /**
     * Copy a directory from one location to another.
     */
    public function copyDirectory(string $directory, string $destination, ?int $options = null): bool
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $options = $options ?: FilesystemIterator::SKIP_DOTS;

        // If the destination directory does not actually exist, we will go ahead and
        // create it recursively, which just gets the destination prepared to copy
        // the files over. Once we make the directory we'll proceed the copying.
        $this->ensureDirectoryExists($destination, 0777);

        $items = new FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            // As we spin through items, we will check to see if the current file is actually
            // a directory or a file. When it is actually a directory we will need to call
            // back into this function recursively to keep copying these nested folders.
            $target = $destination . '/' . $item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();

                if (! $this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            }

            // If the current items is just a regular file, we will just copy this to the new
            // location and keep looping. If for some reason the copy fails we'll bail out
            // and return false, so the developer is aware that the copy process failed.
            elseif (! $this->copy($item->getPathname(), $target)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     */
    public function deleteDirectory(string $directory, bool $preserve = false): bool
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-directory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir() && ! $item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            }

            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and calling directories recursively, so we delete the real path.
            else {
                $this->delete($item->getPathname());
            }
        }

        if (! $preserve) {
            @rmdir($directory);
        }

        return true;
    }

    /**
     * Remove all of the directories within a given directory.
     */
    public function deleteDirectories(string $directory): bool
    {
        $allDirectories = $this->directories($directory);

        if (! empty($allDirectories)) {
            foreach ($allDirectories as $directoryName) {
                $this->deleteDirectory($directoryName);
            }

            return true;
        }

        return false;
    }

    /**
     * Empty the specified directory of all files and folders.
     */
    public function cleanDirectory(string $directory): bool
    {
        return $this->deleteDirectory($directory, true);
    }
}
