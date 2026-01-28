<?php

$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\ModuleServiceProvider::class,
];

// Ensure we always return an array (fixes "foreach() argument must be of type array|object, int given")
return is_array($providers) ? array_values(array_filter($providers, 'is_string')) : [];
