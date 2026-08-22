<?php

return [
    'database_backup_retention' => (int) env('DB_BACKUP_RETENTION', 7),
    'mysqldump_path' => env('MYSQLDUMP_PATH'),
];
