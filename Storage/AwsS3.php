<?php

namespace Angle\FileStorageBundle\Storage;

use Angle\Utilities\SlugUtility;

use Aws\S3\S3Client;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
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


    private function initializeS3Client()
    {
        if (!($this->credentials instanceof Credentials)) {
            throw new \RuntimeException("Credentials were not specified");
        }

        // Must specify signature version
        $config = array(
            'version'       => self::S3_VERSION,
            'signature'     => self::SIGNATURE_VERSION,
            'region'        => $this->bucketRegion,
            'credentials'   => $this->credentials,
            'debug'         => false
        );

        $s3Client = new S3Client($config);

        $this->s3Client = $s3Client;
    }

    /**
     * Writes a file in S3
     *
     * @param $key
     * @param $content
     * @param bool $public
     * @param null $contentType
     * @param null $attachmentFilename
     * @return bool
     * @throws \Exception
     */
    public function s3WriteObject($key, $content, $public = false, $contentType = null, $attachmentFilename = null)
    {
        // Check if file exists already
        if ($this->s3Client->doesObjectExist($this->bucket, $key)) {
            throw new \Exception("Un archivo con el mismo nombre ya existe, por favor verifica esta operación.");
        }

        // Public accessible or not
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
        if ($attachmentFilename) $options['ContentDisposition'] = 'attachment; filename=' . $attachmentFilename;

        // Upload a file. The file size, file type, and MD5 hash
        // are automatically calculated by the SDK.
        try {
            $this->s3Client->putObject($options);
        } catch (S3Exception $e) {
            // For debugging purposes use the following:
            // The AWS error code (e.g., )
            // $e->getAwsErrorCode();
            // $e->getMessage();
            throw new \Exception('La carga del archivo falló: ' . $e->getMessage());
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
    public function s3GetObject($key): \Aws\Result
    {
        // Check if file exists already
        if (!$this->s3Client->doesObjectExist($this->bucket, $key)) {
            throw new NotFoundHttpException('Object key does not exist.');
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
    public function s3GetObjectAsStreamedResponse($key): StreamedResponse
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
     * @param string $filename name to download the file as
     * @throws \Exception
     * @return StreamedResponse
     */
    public function s3GetObjectAsStreamedResponseForDownload($key, $filename): StreamedResponse
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
        $response->setCallback(function () use ($contentType, $body, $filename) {
            header("Content-Type: {$contentType}");
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            echo $body;
        });

        return $response;
    }

    public function s3DeleteObject($key): bool
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


    public function s3GetObjectUrl($key)
    {
        return $this->s3Client->getObjectUrl($this->bucket,$key);
    }


    public function s3ListObjects($prefix = '', $delimiter = '')
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
    public function s3IsDirectory($key)
    {
        $result = $this->s3Client->listObjects(array(
            'Bucket'  => $this->bucket,
            'Prefix'  => rtrim($key, '/') . '/',
            'MaxKeys' => 1
        ));

        return count($result['Contents']) > 0;
    }
}