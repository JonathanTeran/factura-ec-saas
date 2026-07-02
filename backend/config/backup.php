<?php

/*
|--------------------------------------------------------------------------
| Factura EC SaaS - Configuracion de backups (spatie/laravel-backup ^9.4)
|--------------------------------------------------------------------------
| Los backups se ejecutan automaticamente via el scheduler de Laravel
| (ver backend/routes/console.php): backup:run / backup:clean / backup:monitor.
| El contenedor `scheduler` de docker-compose.production.yml corre
| `php artisan schedule:run` cada 60s, por lo que NO hace falta cron externo.
|
| Destino: disco `local` (storage/app/private), persistido en el volumen
| Docker `app_storage`. Retencion: 7 dias (coherente con `deploy.sh backup`).
*/

return [

    'backup' => [

        'name' => env('BACKUP_ARCHIVE_NAME', env('APP_NAME', 'factura-ec')),

        'source' => [
            'files' => [
                /*
                 * Por defecto solo respaldamos la base de datos. El codigo vive
                 * en git y los archivos de usuario en MinIO/S3, asi que no se
                 * incluyen rutas de ficheros para mantener los backups ligeros.
                 * Para incluir storage de la app, agrega base_path('storage/app')
                 * a `include`.
                 */
                'include' => [],

                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                ],

                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => null,
            ],

            /*
             * Conexiones de BD a respaldar. Usa la conexion por defecto (mysql).
             */
            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        /*
         * Compresion gzip (genera archivos .sql.gz, igual que deploy.sh backup).
         */
        'database_dump_compressor' => Spatie\DbDumper\Compressors\GzipCompressor::class,

        'database_dump_file_timestamp_format' => null,

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFLATE,
            'compression_level' => 9,

            'filename_prefix' => 'factura_ec_',

            /*
             * Disco(s) de Laravel donde se guardan los backups.
             * `local` => storage/app/private, persistido por el volumen app_storage.
             * Puedes anadir 's3' para enviar copias a MinIO/S3 (off-site).
             */
            'disks' => [
                'local',
            ],
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),

        'encryption' => 'default',

        'tries' => 1,

        'retry_delay' => 0,
    ],

    /*
     * Notificaciones: avisa por mail si un backup falla o no es saludable.
     */
    'notifications' => [

        'notifications' => [
            Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => [],
            Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => [],
            Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL', 'soporte@amephia.com'),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@amephia.com'),
                'name' => env('MAIL_FROM_NAME', 'Factura EC Backups'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],
    ],

    /*
     * Monitor de salud usado por `backup:monitor`.
     */
    'monitor_backups' => [
        [
            'name' => env('BACKUP_ARCHIVE_NAME', env('APP_NAME', 'factura-ec')),
            'disks' => ['local'],
            'health_checks' => [
                Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup' => [

        'strategy' => Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [

            /*
             * Conserva todos los backups de los ultimos 7 dias (coherente con
             * la rotacion manual de `deploy.sh backup`, que guarda 7 copias).
             */
            'keep_all_backups_for_days' => 7,

            'keep_daily_backups_for_days' => 7,

            'keep_weekly_backups_for_weeks' => 0,

            'keep_monthly_backups_for_months' => 0,

            'keep_yearly_backups_for_years' => 0,

            /*
             * Si el almacenamiento total supera este valor (MB), se borran
             * los backups mas antiguos hasta volver por debajo del limite.
             */
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],
];
