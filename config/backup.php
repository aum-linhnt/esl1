<?php

return [
    'path' => storage_path('app/private/backups'),
    'daily_time' => env('DB_BACKUP_DAILY_TIME', '02:00'),
    'timezone' => env('DB_BACKUP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
];
