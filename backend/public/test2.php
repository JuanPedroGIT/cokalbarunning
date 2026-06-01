<?php
require_once dirname(__DIR__).'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$t0 = microtime(true);
$kernel = new Kernel('dev', true);
$kernel->boot();
$t1 = microtime(true);

$request = Request::create('/api/v1/editions', 'GET');
$response = $kernel->handle($request);
$t2 = microtime(true);

echo json_encode([
    'boot_time' => round($t1 - $t0, 3),
    'handle_time' => round($t2 - $t1, 3),
    'total_time' => round($t2 - $t0, 3),
    'status' => $response->getStatusCode(),
]);
$kernel->terminate($request, $response);
