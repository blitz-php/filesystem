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

use Aws\S3\S3Client;
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
     * Create a new AwsS3V3FilesystemAdapter instance.
     *
     * @param \Aws\S3\S3Client $client The AWS S3 client.
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
        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
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

        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (isset($this->config['temporary_url'])) {
            $uri = $this->replaceBaseUrl($uri, $this->config['temporary_url']);
        }

        return (string) $uri;
    }

    /**
     * Get the underlying S3 client.
     */
    public function getClient(): S3Client
    {
        return $this->client;
    }
}
