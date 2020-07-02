<?php
declare(strict_types=1);

use App\Classes\Route;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/routes/routes.php';

$uri = $_SERVER['REQUEST_URI'];
$method = strtolower($_SERVER['REQUEST_METHOD']);

$router = Route::getInstance();

if (in_array($method, ['post', 'patch'])) {
    $payload = [];
    // This allows for both POST and PATCH to work
    $requestPayload = file_get_contents('php://input');

    if ($_SERVER['HTTP_CONTENT_TYPE'] === 'application/json') {
        $payload = json_decode($requestPayload, true);
    } else {
        parse_str($requestPayload, $payload);
    }

    $router->$method($uri, $payload);
}else {
    $router->$method($uri);
}

