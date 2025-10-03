<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class UploadServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Override PHP upload limits
        ini_set('upload_max_filesize', '10M');
        ini_set('post_max_size', '20M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        ini_set('memory_limit', '256M');
    }

    public function register()
    {
        //
    }
}
