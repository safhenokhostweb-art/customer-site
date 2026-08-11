<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    // دیسک رسانهٔ عمومی (avatar/product/theme images ...). محلی: uploads
    // در Object Storage: arvan یا s3. MEDIA_DISK نام کانونیک است؛ UPLOAD_DISK
    // به‌عنوان fallback قدیمی حفظ شده تا env فعلی هیچ نصبی را نشکند.
    'upload_disk' => env('MEDIA_DISK', env('UPLOAD_DISK', 'uploads')),

    // دیسک محتوای خصوصی (پیوست پشتیبانی/تننت) — هرگز web-accessible نیست.
    'media_private_disk' => env('MEDIA_PRIVATE_DISK', 'private'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        // Fixes an undefined-disk reference: SupportController and
        // TenantAttachmentService already call Storage::disk('private'),
        // this just gives that name an actual definition (never web-accessible).
        'private' => [
            'driver'     => 'local',
            'root'       => storage_path('app/private'),
            'visibility' => 'private',
            'throw'      => false,
            'report'     => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'uploads' => [
            'driver' => 'local',
            'root' => public_path('uploads'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/uploads',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        // ARVAN: Offsite backup disk (ArvanCloud S3-compatible object storage)
        'arvan_backup' => [
            'driver'                  => 's3',
            'key'                     => env('ARVAN_ACCESS_KEY'),
            'secret'                  => env('ARVAN_SECRET_KEY'),
            'region'                  => env('ARVAN_REGION', 'ir-thr-at1'),
            'bucket'                  => env('ARVAN_BUCKET', 'safhenokhost-backups'),
            'endpoint'                => env('ARVAN_ENDPOINT'),
            'url'                     => env('ARVAN_URL'),
            'use_path_style_endpoint' => true,
            'throw'                   => false,
            'visibility'              => 'private',
        ],

        // MEDIA-1: Arvan Object Storage — public-visibility media (avatars,
        // product/theme images). Select via MEDIA_DISK=arvan. Uses its own
        // bucket/prefix (distinct from arvan_backup) so media and backups
        // never mix in the same namespace.
        'arvan' => [
            'driver'                  => 's3',
            'key'                     => env('ARVAN_ACCESS_KEY'),
            'secret'                  => env('ARVAN_SECRET_KEY'),
            'region'                  => env('ARVAN_REGION', 'ir-thr-at1'),
            'bucket'                  => env('ARVAN_MEDIA_BUCKET', env('ARVAN_BUCKET', 'safhenokhost-media')),
            'root'                    => env('ARVAN_MEDIA_PREFIX', 'media'),
            'endpoint'                => env('ARVAN_ENDPOINT'),
            'url'                     => env('ARVAN_MEDIA_URL', env('ARVAN_URL')),
            'use_path_style_endpoint' => true,
            'throw'                   => false,
            'visibility'              => 'public',
        ],

        // MEDIA-1: Arvan Object Storage — private-visibility media (support
        // attachments). Select via MEDIA_PRIVATE_DISK=arvan_private.
        'arvan_private' => [
            'driver'                  => 's3',
            'key'                     => env('ARVAN_ACCESS_KEY'),
            'secret'                  => env('ARVAN_SECRET_KEY'),
            'region'                  => env('ARVAN_REGION', 'ir-thr-at1'),
            'bucket'                  => env('ARVAN_MEDIA_BUCKET', env('ARVAN_BUCKET', 'safhenokhost-media')),
            'root'                    => env('ARVAN_MEDIA_PRIVATE_PREFIX', 'media-private'),
            'endpoint'                => env('ARVAN_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw'                   => false,
            'visibility'              => 'private',
        ],

        // MEDIA-1: plain S3 — private-visibility counterpart to the existing
        // 's3' disk, for installs that use AWS S3 instead of Arvan.
        's3_private' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION'),
            'bucket'                  => env('AWS_BUCKET'),
            'root'                    => env('AWS_PRIVATE_PREFIX', 'private'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => false,
            'visibility'              => 'private',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
