<?php

namespace Angle\FileStorageBundle\Storage;

use Angle\Utilities\SlugUtility;

use Aws\S3\S3Client;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Local implements StorageInterface
{

}