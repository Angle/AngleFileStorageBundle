<?php

namespace Angle\FileStorageBundle\Storage;

use Angle\Utilities\SlugUtility;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\BlobAccessPolicy;
use MicrosoftAzure\Storage\Blob\Models\ContainerACL;
use MicrosoftAzure\Storage\Blob\Models\GetBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\GetBlobResult;
use MicrosoftAzure\Storage\Blob\Models\SetBlobPropertiesOptions;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AzureStorageBlob implements StorageInterface
{
    const CONTAINER_NAME = '??'; // can only be lowercase // TODO: Read from service configuration

    public const ACL_NONE = '';
    public const ACL_BLOB = 'blob';
    public const ACL_CONTAINER = 'container';

    protected ?string $connectionString;

    /** @var BlobRestProxy $blobClient */
    protected $blobClient;

    public function __construct(string $connectionString)
    {
        $this->connectionString = $connectionString;

        $this->initializeBlobClient();
    }

    private function initializeBlobClient(): void
    {
        $this->blobClient = BlobRestProxy::createBlobService($this->connectionString);
    }

    public function writeBlob($content, string $blobKey, $contentType = null, $attachmentFilename = null): string
    {
        $this->blobClient->createBlockBlob(self::CONTAINER_NAME, $blobKey, $content);

        // Configure Blob Options
        $blobOptions = new SetBlobPropertiesOptions();

        // TODO:

        // if specific ContentType wishes to be specified for different file dispositions.
        if ($contentType) $blobOptions->setContentType($contentType);
        // In case of download disposition files, ensure FileName set as desired
        if ($attachmentFilename) {

            // clean up the filename
            setlocale(LC_ALL, 'en_US.UTF-8');
            $extension = pathinfo($attachmentFilename, PATHINFO_EXTENSION);
            $nameWithoutExtension = substr($attachmentFilename, 0, -1-strlen($extension));
            $attachmentFilename = SlugUtility::slugify($nameWithoutExtension, 120) . '.' . $extension; // this will also shorten it if too long

            $blobOptions->setContentDisposition('attachment; filename=' . $attachmentFilename);
        }

        $this->blobClient->setBlobProperties(
            self::CONTAINER_NAME,
            $blobKey,
            $blobOptions
        );

        return $blobKey;
    }

    /**
     * @param string $blobKey
     * @return GetBlobResult
     */
    public function getBlob(string $blobKey): GetBlobResult
    {
        return $this->blobClient->getBlob(self::CONTAINER_NAME, $blobKey);
    }

    /**
     * Get a blob from Azure, return a StreamedResponse ready to be served as a stream
     *
     * @param string $blobKey
     * @throws \Exception
     * @return StreamedResponse
     */
    public function getBlobAsStreamedResponse(string $blobKey): StreamedResponse
    {
        try {
            $r = $this->getBlob($blobKey);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }

        $body = stream_get_contents($r->getContentStream());

        $contentType = $r->getProperties()->getContentType();

        $response = new StreamedResponse();
        $response->setCallback(function () use ($contentType, $body) {
            header("Content-Type: {$contentType}");
            echo $body;
        });

        return $response;
    }

    /**
     * Get a blob from Azure, return a StreamedResponse ready to download
     *
     * @param string $blobKey
     * @param string $filename name to download the file as
     * @throws \Exception
     * @return StreamedResponse
     */
    public function getBlobAsStreamedResponseForDownload(string $blobKey, string $filename): StreamedResponse
    {
        try {
            $r = $this->getBlob($blobKey);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }

        $body = stream_get_contents($r->getContentStream());

        $contentType = $r->getProperties()->getContentType();

        $response = new StreamedResponse();
        $response->setCallback(function () use ($contentType, $body, $filename) {
            header("Content-Type: {$contentType}");
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            echo $body;
        });

        return $response;
    }

    public function deleteBlob(string $blobKey): bool
    {
        try {
            $this->blobClient->deleteBlob(self::CONTAINER_NAME, $blobKey);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }


    // INITIALIZATION ONLY METHODS
    /**
     * Create (initialize) the Blob Container
     * @return void
     */
    public function createBlobContainer(): void
    {
        $this->blobClient->createContainer(self::CONTAINER_NAME);
    }

    /**
     * Configure the Blob Container
     * @param string $acl
     * @return bool
     */
    public function setBlobContainerAcl(string $acl = self::ACL_BLOB): bool
    {
        if (! in_array($acl, [self::ACL_NONE, self::ACL_BLOB, self::ACL_CONTAINER], true)) {
            return false;
        }

        $blobAcl = new ContainerACL();
        $blobAcl->setPublicAccess($acl);

        $this->blobClient->setContainerAcl(
            self::CONTAINER_NAME,
            $blobAcl
        );

        return true;
    }

    // DEBUG METHODS
    /**
     * Create (initialize) the Blob Container
     * @return Blob[]
     */
    public function listBlobsInContainer()
    {
        $blobResults = $this->blobClient->listBlobs(self::CONTAINER_NAME);

        return $blobResults->getBlobs();
    }
}