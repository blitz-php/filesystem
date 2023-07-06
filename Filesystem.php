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

use BlitzPHP\Filesystem\Exceptions\FileNotFoundException;
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
     * Déterminez si un fichier ou un répertoire est manquant.
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

        throw new FileNotFoundException("Le fichier n'existe pas dans le chemin {$path}.");
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
     * Obtenir le contenu d'un fichier une ligne à la fois.
     *
     * @throws FileNotFoundException
     */
    public function lines(string $path): LazyCollection
    {
        if (! $this->isFile($path)) {
            throw new FileNotFoundException("Le fichier n'existe pas dans le chemin {$path}.");
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
        // Si le chemin existe déjà et est un lien symbolique, obtenez le vrai chemin...
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        // Corrigez les autorisations de tempPath car `tempnam()` le crée avec des autorisations définies sur 0600 ...
        if (null !== $mode) {
            chmod($tempPath, $mode);
        } else {
            chmod($tempPath, 0777 - umask());
        }

        file_put_contents($tempPath, $content);

        rename($tempPath, $path);
    }

    /**
     * Remplace une chaîne donnée dans un fichier donné.
     */
    public function replaceInFile(array|string $search, array|string $replace, string $path): void
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    /**
     * Ajouter au début d'un fichier.
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
     * Ajouter à un fichier.
     *
     * @return false|int
     */
    public function append(string $path, string $data)
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }

    /**
     * Obtenir ou définir le mode UNIX d'un fichier ou d'un répertoire.
     */
    public function chmod(string $path, ?int $mode = null): mixed
    {
        if ($mode) {
            return chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    /**
     * Supprimer le fichier à un chemin donné.
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
     * Déplacer un fichier vers un nouvel emplacement.
     */
    public function move(string $path, string $target, bool $overwrite = true): bool
    {
        if (! is_file($target) || ($overwrite && is_file($target))) {
            return rename($path, $target);
        }

        return false;
    }

    /**
     * Copiez un fichier vers un nouvel emplacement.
     */
    public function copy(string $path, string $target, bool $overwrite = true): bool
    {
        if (! is_file($target) || ($overwrite && is_file($target))) {
            return copy($path, $target);
        }

        return false;
    }

    /**
     * Créez un lien symbolique vers le fichier ou le répertoire cible. Sous Windows, un lien physique est créé si la cible est un fichier.
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
     * Extraire le nom de fichier d'un chemin de fichier.
     */
    public function name(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extraire le composant de nom de fin d'un chemin de fichier.
     */
    public function basename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extraire le répertoire parent d'un chemin de fichier.
     */
    public function dirname(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Extraire l'extension de fichier d'un chemin de fichier.
     */
    public function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Obtenir le type de fichier d'un fichier donné.
     */
    public function type(string $path): string
    {
        return filetype($path);
    }

    /**
     * Récupère le type mime d'un fichier donné.
     *
     * @return false|string
     */
    public function mimeType(string $path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Obtenir la taille de fichier d'un fichier donné.
     */
    public function size(string $path): int
    {
        return filesize($path);
    }

    /**
     * Obtenir l'heure de la dernière modification du fichier.
     */
    public function lastModified(string $path): int
    {
        return filemtime($path);
    }

    /**
     * Déterminer si le chemin donné est un répertoire.
     */
    public function isDirectory(string $directory): bool
    {
        return is_dir($directory);
    }

    /**
     * Déterminer si le chemin donné est un répertoire qui ne contient aucun autre fichier ou répertoire.
     */
    public function isEmptyDirectory(string $directory, bool $ignoreDotFiles = false): bool
    {
        return ! Finder::create()->ignoreDotFiles($ignoreDotFiles)->in($directory)->depth(0)->hasResults();
    }

    /**
     * Déterminez si le chemin donné est lisible.
     */
    public function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    /**
     * Détermine si le chemin donné est accessible en écriture.
     */
    public function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    /**
     * Déterminez si deux fichiers sont identiques en comparant leurs hachages.
     */
    public function hasSameHash(string $firstFile, string $secondFile): bool
    {
        $hash = @md5_file($firstFile);

        return $hash && $hash === @md5_file($secondFile);
    }

    /**
     * Déterminez si le chemin donné est un fichier.
     */
    public function isFile(string $file): bool
    {
        return is_file($file);
    }

    /**
     * Trouver les noms de chemin correspondant à un modèle donné.
     *
     * @return array
     */
    public function glob(string $pattern, int $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * Récupère un tableau de tous les fichiers d'un répertoire.
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
     * Récupère tous les fichiers du répertoire donné (récursif).
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
     * Récupère tous les répertoires d'un répertoire donné.
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
     * Assurez-vous qu'un répertoire existe.
     */
    public function ensureDirectoryExists(string $path, int $mode = 0755, bool $recursive = true): void
    {
        if (! $this->isDirectory($path)) {
            $this->makeDirectory($path, $mode, $recursive);
        }
    }

    /**
     * Créez un répertoire.
     */
    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false): bool
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Déplacer un répertoire.
     */
    public function moveDirectory(string $from, string $to, bool $overwrite = false): bool
    {
        if ($overwrite && $this->isDirectory($to) && ! $this->deleteDirectory($to)) {
            return false;
        }

        return @rename($from, $to) === true;
    }

    /**
     * Copiez un répertoire d'un emplacement à un autre.
     */
    public function copyDirectory(string $directory, string $destination, ?int $options = null): bool
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $options = $options ?: FilesystemIterator::SKIP_DOTS;

        // Si le répertoire de destination n'existe pas réellement,
        // nous continuerons et le créerons de manière récursive,
        // ce qui préparera simplement la destination à copier les fichiers.
        // Une fois que nous aurons créé le répertoire, nous procéderons à la copie.
        $this->ensureDirectoryExists($destination, 0777);

        $items = new FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            // Au fur et à mesure que nous parcourrons les éléments, nous vérifierons si le fichier actuel est en fait un répertoire ou un fichier.
            // Lorsqu'il s'agit en fait d'un répertoire, nous devrons rappeler cette fonction de manière récursive pour continuer à copier ces dossiers imbriqués.
            $target = $destination . '/' . $item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();

                if (! $this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            }

            // Si les éléments actuels ne sont qu'un fichier normal, nous le copierons simplement dans le nouvel emplacement et continuerons à boucler.
            // Si, pour une raison quelconque, la copie échoue, nous renflouerons et renverrons false, afin que le développeur sache que le processus de copie a échoué.
            elseif (! $this->copy($item->getPathname(), $target)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Supprimer récursivement un répertoire.
     *
     * Le répertoire lui-même peut éventuellement être conservé.
     */
    public function deleteDirectory(string $directory, bool $preserve = false): bool
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            // Si l'élément est un répertoire, nous pouvons simplement revenir dans la fonction
            // et supprimer ce sous-répertoire,
            // sinon nous supprimerons simplement le fichier
            // et continuerons à parcourir chaque fichier jusqu'à ce que le répertoire soit nettoyé.
            if ($item->isDir() && ! $item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            }

            // Si l'élément n'est qu'un fichier, nous pouvons continuer
            // et le supprimer puisque nous ne faisons que parcourir
            // et parfaire tous les fichiers de ce répertoire
            // et appeler les répertoires de manière récursive,
            // nous supprimons donc le chemin réel.
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
     * Supprimez tous les répertoires d'un répertoire donné.
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
     * Vide le répertoire spécifié de tous les fichiers et dossiers.
     */
    public function cleanDirectory(string $directory): bool
    {
        return $this->deleteDirectory($directory, true);
    }
}
