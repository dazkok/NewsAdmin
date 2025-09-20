<?php

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$bootstrap = new \App\Application\Bootstrap(__DIR__ . '/../.env');
$kernel = $bootstrap->getKernel();

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$response = $kernel->handle([
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI']
]);
$response->send();