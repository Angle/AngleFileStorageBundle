<?php

namespace Angle\FileStorageBundle\Service;

use Angle\FileStorageBundle\Storage\StorageInterface;
use Angle\FileStorageBundle\Storage\Local;
use Angle\FileStorageBundle\Storage\AwsS3;
use Angle\FileStorageBundle\Storage\AzureBlobStorage;
use Angle\FileStorageBundle\Preset\StorageType;

use Symfony\Component\HttpFoundation\StreamedResponse;

class FileStorage implements StorageInterface
{

    private string $type;
    private StorageInterface $storage;


    public function __construct($config, string $kernelDir)
    {
        if (!StorageType::exists($config['type'])) {
            throw new \RuntimeException('Unknown FileStorageType: ' . $config['type']);
        }

        $this->type = $config['type'];

        // try to initialize the storage client
        if ($this->type == StorageType::LOCAL) {
            $this->storage = new Local($config['container'], $kernelDir);

        } elseif ($this->type == StorageType::AWS_S3) {
            $this->storage = new AwsS3($config['username'], $config['secret'], $config['container'], $config['aws_region']);

        } elseif ($this->type == StorageType::AZURE_BLOB_STORAGE) {
            $this->storage = new AzureBlobStorage($config['username'], $config['secret'], $config['container']);
        }
    }

    public function getType(): string
    {
        return $this->type;
    }



    #########################
    ##      INTERFACE      ##
    #########################

    /**
     * @inheritDoc
     */
    public function exists(string $key): bool
    {
        return $this->storage->exists($key);
    }

    /**
     * @inheritDoc
     */
    public function write(string $key, $content, $contentType = null, $originalName = null): bool
    {
        return $this->storage->write($key, $content, $contentType, $originalName);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return $this->storage->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function getAsStreamedResponse(string $key): StreamedResponse
    {
        return $this->storage->getAsStreamedResponse($key);
    }

    /**
     * @inheritDoc
     */
    public function getAsDownloadResponse(string $key, string $downloadFileName): StreamedResponse
    {
        return $this->storage->getAsDownloadResponse($key, $downloadFileName);
    }



    #########################
    ##      EXTENSION      ##
    #########################

    public function getStorageEngine(): StorageInterface
    {
        return $this->storage;
    }
}