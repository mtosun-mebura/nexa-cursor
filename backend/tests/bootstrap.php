<?php

require dirname(__DIR__).'/vendor/autoload.php';

/*
 * Geen APP_KEY in phpunit.xml: GitHub secret scanning markeert Laravel-keys
 * in de repo. Tests krijgen hier een deterministische dummy (niet productie).
 */
$key = 'base64:'.base64_encode(hash('sha256', 'nexa-phpunit-testing-key', true));
$_ENV['APP_KEY'] = $key;
$_SERVER['APP_KEY'] = $key;
putenv('APP_KEY='.$key);
