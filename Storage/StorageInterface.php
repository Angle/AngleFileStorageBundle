<?php

namespace Angle\FileStorageBundle\Storage;

use Symfony\Component\HttpFoundation\StreamedResponse;

interface StorageInterface
{
    /**
     * Check if a file with the given Key already exists in the Storage
     *
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * Write (upload) file to the Storage
     *
     * @param $s3Key
     * @param $content
     * @param string $contentType MIME Type
     * @param $originalName
     * @return bool
     */
    public function write(string $key, $content, string $contentType = null, string $originalName = null): bool;

    /**
     * Delete a file from the Storage
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Get a file from the Storage as a StreamedResponse, ready to serve via HTTP
     *
     * @param string $key
     * @return StreamedResponse
     */
    public function getAsStreamedResponse(string $key): StreamedResponse;

    /**
     * Get a file from the Storage as a DownloadableStreamedResponse, ready to serve via HTTP and will automatically
     * trigger a Content-Disposition header
     *
     * @param string $key
     * @param string $downloadFileName
     * @return StreamedResponse
     */
    public function getAsDownloadResponse(string $key, string $downloadFileName): StreamedResponse;

    // TODO: list()
    // public function list(string $path): array;
}