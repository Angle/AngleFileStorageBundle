# Angle FileStorage Bundle
Multi-backend file storage bundle. Supported backends: local, AWS S3, Azure Storage Blob.


configuration
->scalarNode('type')->defaultValue('local')->end()
->scalarNode('container')->defaultNull()->end()
->scalarNode('username')->defaultNull()->end()
->scalarNode('secret')->defaultNull()->end()
->scalarNode('aws_region')->defaultNull()->end()

### Azure

AZURE_STORAGE_BLOB_CONNECTION_STRING=DefaultEndpointsProtocol=https;AccountName=yourAccount;AccountKey=yourKey


### AWS
aws_s3_access_key=MYACESSKEY
aws_s3_secret_access_key=MYSECRETPASSWORD
aws_s3_bucket=bucketname
aws_s3_bucket_region=region




    App\Service\DocumentStorage:
        arguments:
            $storageType: '%env(storage_type)%'

    App\Service\Aws:
        arguments:
            $accessKey: '%env(aws_s3_access_key)%'
            $secretKey: '%env(aws_s3_secret_access_key)%'
            $bucket: '%env(aws_s3_bucket)%'
            $bucketRegion: '%env(aws_s3_bucket_region)%'

    App\Service\AzureStorageBlob:
        arguments:
            $connectionString: '%env(AZURE_STORAGE_BLOB_CONNECTION_STRING)%'