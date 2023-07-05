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
use Countable;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * Représentation d'un groupe de fichiers, avec des utilitaires pour les localiser, les filtrer et les classer.
 *
 * @template-implements IteratorAggregate<int, File>
 * 
 * @credit <a href="http://codeigniter.com">CodeIgniter 4 - \CodeIgniter\Files\FileCollector</a>
 */
class FileCollection implements Countable, IteratorAggregate
{
    /**
     * La liste actuelle des chemins de fichiers.
     *
     * @var string[]
     */
    protected array $files = [];

    // --------------------------------------------------------------------
    // Méthodes d'assistance
    // --------------------------------------------------------------------

    /**
     * Résout un chemin complet et vérifie qu'il s'agit d'un répertoire réel.
     *
     * @throws FileException
     */
    final protected static function resolveDirectory(string $directory): string
    {
        if (! is_dir($directory = set_realpath($directory))) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

            throw FileException::expectedDirectory($caller['function']);
        }

        return $directory;
    }

    /**
     * Résout un chemin complet et vérifie qu'il s'agit d'un fichier réel.
     *
     * @throws FileException
     */
    final protected static function resolveFile(string $file): string
    {
        if (! is_file($file = set_realpath($file))) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

            throw FileException::expectedFile($caller['function']);
        }

        return $file;
    }

    /**
     * Supprime les fichiers qui ne font pas partie du répertoire donné (récursif).
     *
     * @param string[] $files
     *
     * @return string[]
     */
    final protected static function filterFiles(array $files, string $directory): array
    {
        $directory = self::resolveDirectory($directory);

        return array_filter($files, static fn (string $value): bool => strpos($value, $directory) === 0);
    }

    /**
     * Renvoie tous les fichiers dont le `basename` correspond au modèle donné.
     *
     * @param string[] $files
     * @param string   $pattern Chaîne régulière ou pseudo-régulière
     *
     * @return string[]
     */
    final protected static function matchFiles(array $files, string $pattern): array
    {
        // Convertir les pseudo-regex dans leur vraie forme
        if (@preg_match($pattern, '') === false) {
            $pattern = str_replace(
                ['#', '.', '*', '?'],
                ['\#', '\.', '.*', '.'],
                $pattern
            );
            $pattern = "#{$pattern}#";
        }

        return array_filter($files, static fn ($value) => (bool) preg_match($pattern, basename($value)));
    }

    // --------------------------------------------------------------------
    // Class Core
    // --------------------------------------------------------------------

    /**
     * Charge l'assistant du système de fichiers et ajoute tous les fichiers initiaux.
     *
     * @param string[] $files
     */
    public function __construct(array $files = [])
    {
        helper('filesystem');

        $this->add($files)->define();
    }

    /**
     * Applique toutes les entrées initiales après le constructeur.
     * Cette méthode est un stub à implémenter par les classes enfants.
     */
    protected function define(): void
    {
    }

    /**
     * Optimise et renvoie la liste de fichiers actuelle.
     *
     * @return string[]
     */
    public function get(): array
    {
        $this->files = array_unique($this->files);
        sort($this->files, SORT_STRING);

        return $this->files;
    }

    /**
     * Définit directement la liste des fichiers, les fichiers sont toujours soumis à vérification.
     * Cela fonctionne comme une méthode de "réinitialisation" avec [].
     *
     * @param string[] $files La nouvelle liste de fichiers à utiliser
     */
    public function set(array $files): self
    {
        $this->files = [];

        return $this->addFiles($files);
    }

    /**
     * Ajoute un tableau/fichier unique ou un répertoire à la liste.
     */
    public function add(array|string $paths, bool $recursive = true): self
    {
        $paths = (array) $paths;

        foreach ($paths as $path) {
            if (! is_string($path)) {
                throw new InvalidArgumentException('Les chemins FileCollection doivent être des chaînes.');
            }

            try {
                // Tester un répertoire
                self::resolveDirectory($path);
            } catch (FileException $e) {
                $this->addFile($path);

                continue;
            }

            $this->addDirectory($path, $recursive);
        }

        return $this;
    }

    // --------------------------------------------------------------------
    // Gestion des fichiers
    // --------------------------------------------------------------------

    /**
     * Vérifie et ajoute des fichiers à la liste.
     *
     * @param string[] $files
     */
    public function addFiles(array $files): self
    {
        foreach ($files as $file) {
            $this->addFile($file);
        }

        return $this;
    }

    /**
     * Vérifie et ajoute un seul fichier à la liste des fichiers.
     */
    public function addFile(string $file): self
    {
        $this->files[] = self::resolveFile($file);

        return $this;
    }

    /**
     * Supprime les fichiers de la liste.
     *
     * @param string[] $files
     */
    public function removeFiles(array $files): self
    {
        $this->files = array_diff($this->files, $files);

        return $this;
    }

    /**
     * Supprime un seul fichier de la liste.
     */
    public function removeFile(string $file): self
    {
        return $this->removeFiles([$file]);
    }

    // --------------------------------------------------------------------
    // Gestion des dossiers
    // --------------------------------------------------------------------

    /**
     * Vérifie et ajoute les fichiers de chaque répertoire à la liste.
     *
     * @param string[] $directories
     */
    public function addDirectories(array $directories, bool $recursive = false): self
    {
        foreach ($directories as $directory) {
            $this->addDirectory($directory, $recursive);
        }

        return $this;
    }

    /**
     * Vérifie et ajoute tous les fichiers d'un répertoire.
     */
    public function addDirectory(string $directory, bool $recursive = false): self
    {
        $directory = self::resolveDirectory($directory);

        // Mappez le répertoire à la profondeur 2 pour que les répertoires deviennent des tableaux
        foreach (directory_map($directory, 2, true) as $key => $path) {
            if (is_string($path)) {
                $this->addFile($directory . $path);
            } elseif ($recursive && is_array($path)) {
                $this->addDirectory($directory . $key, true);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------
    // Filtres
    // --------------------------------------------------------------------

    /**
     * Supprime tous les fichiers de la liste qui correspondent au modèle fourni (dans la portée facultative).
     *
     * @param string $pattern Regex ou chaîne pseudo-regex
     * @param string|null $scope Le répertoire pour limiter la portée
     */
    public function removePattern(string $pattern, ?string $scope = null): self
    {
        if ($pattern === '') {
            return $this;
        }

        // Commencer par tous les fichiers ou ceux concernés
        $files = $scope === null ? $this->files : self::filterFiles($this->files, $scope);

        // Supprimez tous les fichiers qui correspondent au modèle
        return $this->removeFiles(self::matchFiles($files, $pattern));
    }

    /**
     * Conserve uniquement les fichiers de la liste qui correspondent (dans la portée facultative).
     *
     * @param string $pattern Regex ou chaîne pseudo-regex
     * @param string|null $scope Un répertoire pour limiter la portée
     */
    public function retainPattern(string $pattern, ?string $scope = null): self
    {
        if ($pattern === '') {
            return $this;
        }

        // Commencer par tous les fichiers ou ceux concernés
        $files = $scope === null ? $this->files : self::filterFiles($this->files, $scope);

        // Correspond au modèle dans les fichiers délimités et supprime leur inverse.
        return $this->removeFiles(array_diff($files, self::matchFiles($files, $pattern)));
    }

    // --------------------------------------------------------------------
    // Methods d'interfaces
    // --------------------------------------------------------------------

    /**
     * Renvoie le nombre actuel de fichiers dans la collection.
     */
    public function count(): int
    {
        return count($this->files);
    }

    /**
     * Produit en tant qu'itérateur pour les fichiers actuels.
     *
     * @return Generator<File>
     *
     * @throws FileNotFoundException
     */
    public function getIterator(): Generator
    {
        foreach ($this->get() as $file) {
            yield new File($file, true);
        }
    }
}
