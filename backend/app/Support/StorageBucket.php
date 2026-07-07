<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Garantiza que el bucket de S3/MinIO exista. Si el almacenamiento se recrea
 * desde cero (bucket ausente), esto lo crea automáticamente para que no se
 * rompa la generación de RIDE/XML.
 */
class StorageBucket
{
    public static function ensure(): void
    {
        if (config('filesystems.default') !== 's3') {
            return;
        }

        $bucket = config('filesystems.disks.s3.bucket');
        if (empty($bucket)) {
            return;
        }

        /** @var \Aws\S3\S3Client $client */
        $client = Storage::disk('s3')->getClient();

        if (! $client->doesBucketExist($bucket)) {
            $client->createBucket(['Bucket' => $bucket]);
        }
    }
}
