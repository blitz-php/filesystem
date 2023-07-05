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

use Aws\S3\S3Client;
use BlitzPHP\Filesystem\FilesystemAdapter;
use BlitzPHP\Traits\Conditionable;
use DateTimeInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\FilesystemOperator;

/**
 * @credit <a href="http://laravel.com/">Laravel - Illuminate\Filesystem\AwsS3V3Adapter</a>
 */
class AwsS3V3Adapter extends FilesystemAdapter
{
    use Conditionable;

    /**
     * Créez une nouvelle instance AwsS3V3FilesystemAdapter.
     *
     * @param \Aws\S3\S3Client $client Le client AWS S3.
     */
    public function __construct(FilesystemOperator $driver, S3Adapter $adapter, array $config, protected S3Client $client)
    {
        parent::__construct($driver, $adapter, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function url(string $path): string
    {
        // Si une URL de base explicite a été définie sur la configuration du disque, nous l'utiliserons comme URL de base au lieu du chemin par défaut.
        // Cela permet au développeur d'avoir un contrôle total sur le chemin de base des URL générées par ce système de fichiers.
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $this->prefixer->prefixPath($path));
        }

        return $this->client->getObjectUrl(
            $this->config['bucket'],
            $this->prefixer->prefixPath($path)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function providesTemporaryUrls(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        $command = $this->client->getCommand('GetObject', array_merge([
            'Bucket' => $this->config['bucket'],
            'Key'    => $this->prefixer->prefixPath($path),
        ], $options));

        $uri = $this->client->createPresignedRequest(
            $command,
            $expiration,
            $options
        )->getUri();

        // Si une URL de base explicite a été définie sur la configuration du disque, nous l'utiliserons comme URL de base au lieu du chemin par défaut.
        // Cela permet au développeur d'avoir un contrôle total sur le chemin de base des URL générées par ce système de fichiers.
        if (isset($this->config['temporary_url'])) {
            $uri = $this->replaceBaseUrl($uri, $this->config['temporary_url']);
        }

        return (string) $uri;
    }

    /**
     * Obtenez le client S3 sous-jacent.
     */
    public function getClient(): S3Client
    {
        return $this->client;
    }
}
