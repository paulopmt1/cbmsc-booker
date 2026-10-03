<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    $envFile = dirname(__DIR__).'/.env';
    (new Dotenv())->bootEnv(is_file($envFile) ? $envFile : $envFile.'.test');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
