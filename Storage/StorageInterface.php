<?php

namespace Angle\FileStorageBundle\Storage;

use Symfony\Component\HttpFoundation\StreamedResponse;

interface StorageInterface
{
    public function exists();

    public function write();

    public function delete();

    public function getAsStreamedResponse(): StreamedResponse;

    public function getAsDownloadResponse(): StreamedResponse;
}