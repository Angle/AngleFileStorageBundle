<?php

namespace Angle\FileStorageBundle\Storage;

use Angle\Utilities\SlugUtility;

use Aws\S3\S3Client;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use GuzzleHttp\Psr7\Stream;

class Local implements StorageInterface
{
    private ?string $directory;

    public function __construct(?string $container, ?string $kernelDir)
    {
        if (!$container) {
            // default to the default kernel directory
            $this->directory = $kernelDir . '/var/file-storage/';
        } else {
            if (str_starts_with($container, '/')) {
                // path is absolute
                $this->directory = $container;
            } else {
                // path is relative, we will place it within the var folder of symfony
                $this->directory = $kernelDir . '/var/' . $container;
            }
        }

        // try to access the directory
        if (!is_dir($this->directory)) {
            if (!mkdir($this->directory, 0755, true)) {
                throw new \RuntimeException('Cannot access Local directory for FileStorage');
            }
        }

        $this->directory = realpath($this->directory);
    }


    #########################
    ##      INTERFACE      ##
    #########################

    /**
     * @inheritDoc
     */
    public function exists(string $key): bool
    {
        return is_readable($this->getFileName($key));
    }

    /**
     * @inheritDoc
     */
    public function write(string $key, $content, $contentType = null, $originalName = null): bool
    {
        // TODO: check if the path exists before trying to write the file
        // Check if directory exists
        if (!is_dir($this->getFileDirectory($key))) {
            mkdir($this->getFileDirectory($key), 0755, true);
        }

        $r = file_put_contents($this->getFileName($key), $content);

        return ($r !== false);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key)
    {
        $r = unlink($this->getFileName($key));

        return ($r !== false);
    }

    /**
     * @inheritDoc
     */
    public function getAsStreamedResponse(string $key): StreamedResponse
    {
        // Infer the MIME Type from the file (using ext-fileinfo)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_file($finfo, $this->getFileName($key));
        finfo_close($finfo);

        $resource = fopen($this->getFileName($key), 'r');
        $stream = new Stream($resource);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($contentType, $stream) {
            if ($contentType) {
                header("Content-Type: {$contentType}");
            }

            echo $stream;
        });

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function getAsDownloadResponse(string $key, string $downloadFileName): StreamedResponse
    {
        // Infer the MIME Type from the file (using ext-fileinfo)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_file($finfo, $this->getFileName($key));
        finfo_close($finfo);

        $resource = fopen($this->getFileName($key), 'r');
        $stream = new Stream($resource);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($contentType, $stream, $downloadFileName) {
            if ($contentType) {
                header("Content-Type: {$contentType}");
            }

            header("Content-Disposition: attachment; filename=\"{$downloadFileName}\"");
            echo $stream;
        });

        return $response;
    }


    #########################
    ##        CUSTOM       ##
    #########################

    /**
     * Build the actual filename in the Local File System
     * @param string $key
     * @return string
     */
    private function getFileName(string $key): string
    {
        return $this->directory . '/' . $key;
    }

    /**
     * Find the directory where a file resides
     * @param string $filepath
     * @return string
     */
    private function getFileDirectory(string $key): string
    {
        return dirname($this->getFileName($key));
    }

}