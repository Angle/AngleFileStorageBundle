# Angle FileStorage Bundle
Multi-backend file storage bundle. Supported backends: local, AWS S3, Azure Storage Blob.

Supported types:
- `local`
- `aws_s3`
- `azure_blob_storage`

create a configuration file in `symfony/config/packages/angle_file_storage.yaml`:

```yaml
angle_file_storage:
    type: local
    container: file-storage
    username: null
    secret: null
    aws_region: null
```

or link it to the `.env` variables:

```yaml
angle_file_storage:
  type: "%env(ANGLE_FILE_STORAGE_TYPE)%"
  container: "%env(ANGLE_FILE_STORAGE_CONTAINER)%"
  username: "%env(ANGLE_FILE_STORAGE_USERNAME)%"
  secret: "%env(ANGLE_FILE_STORAGE_SECRET)%"
  aws_region: "%env(ANGLE_FILE_STORAGE_AWS_REGION)%"
```

and then in the `.env` add:

```dotenv
###> angle/file-storage-bundle ###
ANGLE_FILE_STORAGE_TYPE="local"
ANGLE_FILE_STORAGE_CONTAINER="container_or_path"
ANGLE_FILE_STORAGE_USERNAME="myusername"
ANGLE_FILE_STORAGE_SECRET="mysecret"
ANGLE_FILE_STORAGE_AWS_REGION=null
###< angle/file-storage-bundle ###
```

### Local
```yaml
angle_file_storage:
    type: local
    container: path/to/files
```

- if path is absolute, it will be taken as is.
- if path is relative, it will be created inside `symfony/var/` 

### Azure
```yaml
angle_file_storage:
    type: azure_blob_storage
    container: azure_blob_container_name
    username: azure_account_name
    secret: azure_account_key
```


### AWS
```yaml
angle_file_storage:
    type: aws_s3
    container: aws_s3_bucket_name
    username: aws_s3_access_key
    secret: aws_s3_secret_access_key
    aws_region: aws_s3_bucket_region
```