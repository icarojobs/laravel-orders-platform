<?php

/*
 * Measures query count and in-process latency of the orders API.
 *
 *   docker compose exec app php benchmarks/orders-api.php [iterations]
 */

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

$iterations = (int) ($argv[1] ?? 30);
$user = User::query()->where('email', 'admin@example.com')->firstOrFail();
$token = $user->createToken('benchmark')->plainTextToken;
$orderId = Order::query()->latest('id')->value('id');

$scenarios = [
    'list 50 orders' => '/api/v1/orders?per_page=50',
    'list paid orders in march' => '/api/v1/orders?per_page=50&status=paid&from='.now()->subDays(30)->toDateString().'&to='.now()->toDateString(),
    'list orders of a customer' => '/api/v1/orders?per_page=50&customer_id=10',
    'show one order' => "/api/v1/orders/{$orderId}",
];

$queries = 0;
DB::listen(function () use (&$queries) {
    $queries++;
});

printf("%-28s %8s %10s %10s %10s\n", 'scenario', 'queries', 'p50 ms', 'p95 ms', 'status');

foreach ($scenarios as $name => $uri) {
    $timings = [];
    $status = 0;
    $perRequest = 0;

    for ($i = 0; $i < $iterations; $i++) {
        $app->forgetInstance('auth');
        $app['auth']->forgetGuards();

        $request = Request::create($uri, 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Authorization', "Bearer {$token}");

        $queries = 0;
        $start = hrtime(true);
        $response = $app->make(HttpKernel::class)->handle($request);
        $timings[] = (hrtime(true) - $start) / 1e6;
        $perRequest = $queries;
        $status = $response->getStatusCode();
    }

    sort($timings);
    printf(
        "%-28s %8d %10.1f %10.1f %10d\n",
        $name,
        $perRequest,
        $timings[(int) floor(count($timings) * 0.5)],
        $timings[(int) min(count($timings) - 1, floor(count($timings) * 0.95))],
        $status,
    );
}

$user->tokens()->where('name', 'benchmark')->delete();
