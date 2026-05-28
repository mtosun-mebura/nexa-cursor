<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class UploadServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        ini_set('upload_max_filesize', (string) config('upload.max_filesize', '512M'));
        ini_set('post_max_size', (string) config('upload.post_max_size', '520M'));
        ini_set('max_execution_time', (string) config('upload.max_execution_time', 600));
        ini_set('max_input_time', (string) config('upload.max_input_time', 600));
        ini_set('memory_limit', (string) config('upload.memory_limit', '512M'));
    }

    public function register()
    {
        //
    }
}
