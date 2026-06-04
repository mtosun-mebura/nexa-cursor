<?php

return [

    /*
    | Standaard PHP-limieten (worden in UploadServiceProvider gezet).
    */
    'max_filesize' => env('UPLOAD_MAX_FILESIZE', '512M'),
    'post_max_size' => env('POST_MAX_SIZE', '520M'),
    'max_execution_time' => (int) env('UPLOAD_MAX_EXECUTION_TIME', 600),
    'max_input_time' => (int) env('UPLOAD_MAX_INPUT_TIME', 600),
    'memory_limit' => env('UPLOAD_MEMORY_LIMIT', '512M'),

    /*
    | Tenant-ZIP import (admin settings): Laravel validatie max in kilobytes.
    */
    'tenant_bundle_max_kb' => (int) env('TENANT_BUNDLE_MAX_KB', 512000),

];
