<?php

namespace Angle\FileStorageBundle\Service;

use Angle\FileStorageBundle\Storage\AwsS3;
use Angle\FileStorageBundle\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileStorage
{

    private StorageInterface $storage;


    public function __construct($config)
    {

        // try to initialize the storage client
        if ()
        $this->storage = new AwsS3();
    }

    public function exists();

    public function write();

    public function delete();

    public function getAsStreamedResponse(): StreamedResponse;

    public function getAsDownloadResponse(): StreamedResponse;

}