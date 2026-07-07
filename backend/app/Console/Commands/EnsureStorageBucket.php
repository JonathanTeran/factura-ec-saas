<?php

namespace App\Console\Commands;

use App\Support\StorageBucket;
use Illuminate\Console\Command;

class EnsureStorageBucket extends Command
{
    protected $signature = 'storage:ensure-bucket';

    protected $description = 'Crea el bucket de S3/MinIO si no existe.';

    public function handle(): int
    {
        try {
            StorageBucket::ensure();
            $this->info('Bucket de almacenamiento verificado/creado.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('No se pudo verificar/crear el bucket: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
