# Angle FileStorage Bundle
Multi-backend file storage bundle. Supported backends: local, AWS S3, Azure Storage Blob.

Supported types
`local`
`aws_s3`
`azure_blob_storage`

configuration
->scalarNode('type')->defaultValue('local')->end()
->scalarNode('container')->defaultNull()->end()
->scalarNode('username')->defaultNull()->end()
->scalarNode('secret')->defaultNull()->end()
->scalarNode('aws_region')->defaultNull()->end()

### Local
type=local
container=path/to/files

if path is absolute, it will be taken as is.
if path is relative, it will be inside symfony/var/ folder

### Azure

AZURE_STORAGE_BLOB_CONNECTION_STRING=DefaultEndpointsProtocol=https;AccountName=yourAccount;AccountKey=yourKey


### AWS

type=aws_s3
container=aws_s3_bucket_name
username=aws_s3_access_key
secret=aws_s3_secret_access_key
aws_region=aws_s3_bucket_region



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