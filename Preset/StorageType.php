<?php

namespace Angle\FileStorageBundle\Preset;

abstract class StorageType
{
    const LOCAL                 = 'local';
    const AWS_S3                = 'aws_s3';
    const AZURE_BLOB_STORAGE    = 'azure_blob_storage';


    private static $map = [
        self::LOCAL => [
            'name'      => 'Local',
            'long_name' => 'Local File System',
            'vendor'    => 'Local',
            'service'   => 'File System',
        ],
        self::AWS_S3 => [
            'name'      => 'AWS S3',
            'long_name' => 'Amazon Web Services Simple Storage Service',
            'vendor'    => 'AWS',
            'service'   => 'S3',
        ],
        self::AZURE_BLOB_STORAGE => [
            'name'      => 'Azure Blob Storage',
            'long_name' => 'Azure Blob Storage',
            'vendor'    => 'Azure',
            'service'   => 'Blob Storage',
        ],
    ];

    public static function listForFormBuilder(): array
    {
        $a = [];

        foreach (self::$map as $key => $props) {
            if ($key !== 0) {
                $a[$props['name']] = $key;
            }
        }

        return $a;
    }

    public static function getMap(): array
    {
        return self::$map;
    }

    public static function getName($id): ?string
    {
        if (!self::exists($id)) {
            return null;
        }

        return self::$map[$id]['name'];
    }

    public static function exists($id): bool
    {
        return array_key_exists($id, self::$map);
    }
}