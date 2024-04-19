<?php

namespace Angle\FileStorageBundle\Storage;

use Angle\Utilities\SlugUtility;

use Aws\Exception\AwsException;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AwsS3 implements StorageInterface
{
    private $accessKey;
    private $secretKey;
    private $bucket;
    private $bucketRegion;

    /** @var Credentials $credentials */
    private $credentials;

    /** @var S3Client $s3Client */
    private $s3Client;

    const S3_VERSION = "2006-03-01";
    const SIGNATURE_VERSION = "v4";


    public function __construct($accessKey, $secretKey, $bucket = '', $bucketRegion = '')
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;

        // S3 Specific
        $this->bucket = $bucket;
        $this->bucketRegion = $bucketRegion;

        if ($this->accessKey && $this->secretKey) {
            $this->credentials = new Credentials($this->accessKey, $this->secretKey);
            $this->initializeS3Client();
        }
    }


    private function initializeS3Client(): void
    {
        if (!($this->credentials instanceof Credentials)) {
            throw new \RuntimeException("AWS S3 Credentials were not specified");
        }

        // Must specify signature version
        $config = array(
            'version'       => self::S3_VERSION,
            'signature'     => self::SIGNATURE_VERSION,
            'region'        => $this->bucketRegion,
            'credentials'   => $this->credentials,
            'debug'         => false
        );

        try {
            $s3Client = new S3Client($config);
        } catch (\Throwable $e) {
            throw new \RuntimeException("AWS S3 failed to initialize: " . $e->getMessage());
        }

        $this->s3Client = $s3Client;
    }


    #########################
    ##      INTERFACE      ##
    #########################

    public function exists(string $key): bool
    {
        return $this->s3Client->doesObjectExist($this->bucket, $key);
    }

    /**
     * Writes a file in S3
     *
     * @param $key
     * @param $content
     * @param null $contentType
     * @param null $originalName
     * @return bool
     * @throws \Exception
     */
    public function write($key, $content, $contentType = null, $originalName = null): bool
    {
        // Check if file exists already
        if ($this->s3Client->doesObjectExist($this->bucket, $key)) {
            throw new \Exception("AWS S3 FileStorage error: an object already exists for the given Key.");
        }

        // Public accessible or not
        // by default all files will be configured as private
        $public = false;
        $acl = ($public ? 'public-read' : 'private');

        // Define options for upload
        $options = array(
            'Bucket' => $this->bucket,
            'Key'    => $key,
            'Body'   => $content,
            'ACL'    => $acl,
        );

        // if specific ContentType wishes to be specified for different file dispositions.
        if ($contentType) $options['ContentType'] = $contentType;
        // In case of download disposition files, ensure FileName set as desired
        if ($originalName) $options['ContentDisposition'] = 'attachment; filename=' . $originalName;

        // Upload a file. The file size, file type, and MD5 hash
        // are automatically calculated by the SDK.
        try {
            $this->s3Client->putObject($options);
        } catch (S3Exception $e) {
            // For debugging purposes use the following:
            // The AWS error code (e.g., )
            // $e->getAwsErrorCode();
            // $e->getMessage();
            throw new \Exception('AWS S3 FileStorage failed: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Get a file from S3
     *
     * @param $key
     * @throws \Exception
     * @return \Aws\Result
     */
    private function s3GetObject($key): \Aws\Result
    {
        // Check if file exists already
        if (!$this->s3Client->doesObjectExist($this->bucket, $key)) {
            throw new NotFoundHttpException('AWS S3 FileStorage: object key does not exist.');
        }

        return $this->s3Client->getObject([
            'Bucket' => $this->bucket,
            'Key'   => $key,
        ]);
    }

    /**
     * Get a file from S3, returns a StreamedResponse ready to be served as a stream
     *
     * @param $key
     * @throws \Exception
     * @return StreamedResponse
     */
    public function getAsStreamedResponse(string $key): StreamedResponse
    {
        try {
            $r = $this->s3GetObject($key);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }

        /** @var \GuzzleHttp\Psr7\Stream $body */
        $body = $r['Body'];

        $contentType = $r['ContentType'];

        $response = new StreamedResponse();
        $response->setCallback(function () use ($contentType, $body) {
            header("Content-Type: {$contentType}");
            echo $body;
        });

        return $response;
    }

    /**
     * Get a file from S3, returns a StreamedResponse ready to download
     *
     * @param string $key S3 object key
     * @param string $downloadFileName name to download the file as
     * @throws \Exception
     * @return StreamedResponse
     */
    public function getAsDownloadResponse(string $key, string $downloadFileName): StreamedResponse
    {
        try {
            $r = $this->s3GetObject($key);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }

        /** @var \GuzzleHttp\Psr7\Stream $body */
        $body = $r['Body'];

        $contentType = $r['ContentType'];

        $response = new StreamedResponse();
        $response->setCallback(function () use ($contentType, $body, $downloadFileName) {
            header("Content-Type: {$contentType}");
            header("Content-Disposition: attachment; filename=\"{$downloadFileName}\"");
            echo $body;
        });

        return $response;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        // Define options for deletion
        $options = array(
            'Bucket' => $this->bucket,
            'Key'    => $key
        );


        try {
            $this->s3Client->deleteObject($options);
        } catch (S3Exception $e) {
            // For debugging purposes use the following:
            // The AWS error code (e.g., )
            // $e->getAwsErrorCode();
            // $e->getMessage();
            return false;
        }

        return true;
    }


    #########################
    ##        CUSTOM       ##
    #########################

    public function s3GetObjectUrl($key): string
    {
        return $this->s3Client->getObjectUrl($this->bucket,$key);
    }


    public function s3ListObjects($prefix = '', $delimiter = ''): array
    {
        // Define options for listing
        $options = array(
            'Bucket' => $this->bucket,
        );

        $iteratorOptions = array(
            'return_prefixes' => false
        );

        if ($prefix !== '') $options['Prefix'] = $prefix;
        if ($delimiter !== '') $options['Delimiter'] = $delimiter;
        if ($prefix !== '' || $delimiter !== '') $iteratorOptions['return_prefixes'] = true;

        $iterator = $this->s3Client->getIterator('ListObjects', $options, $iteratorOptions);

        $keys = array();

        foreach ($iterator as $object) {
            if (isset($object['Prefix'])) {
                $keys[] = $object['Prefix'];
            }else{
                $keys[] = $object['Key'];
            }
        }

        return $keys;
    }

    /**
     * Helper function for browser. Check if key is the end of path
     *
     * @param $key
     * @return bool
     */
    public function s3IsDirectory($key): bool
    {
        $result = $this->s3Client->listObjects(array(
            'Bucket'  => $this->bucket,
            'Prefix'  => rtrim($key, '/') . '/',
            'MaxKeys' => 1
        ));

        return count($result['Contents']) > 0;
    }
}