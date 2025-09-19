<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;

session_start();
$bootstrap = new Bootstrap(__DIR__ . '/../.env');
$router = $bootstrap->getRouter();

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);