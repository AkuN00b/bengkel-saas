<?php

use App\Actions\Billing;
use App\Actions\Inventory;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
TestDatabaseGuard::ensureSafe($app);
if (! in_array(config('database.connections.pgsql.host'), ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new RuntimeException('Local test database required.');
}
$input = json_decode($argv[3], true, 512, JSON_THROW_ON_ERROR);
try {
    $workshop = Workshop::findOrFail($argv[1]);
    $actor = User::findOrFail($argv[2]);
    if ($input['op'] === 'return') {
        app(Inventory::class)->move($workshop, $actor, $input['part'], $input['data'], 'work_order_return', $input['order'], $input['line'], $input['source'], true);
    } elseif ($input['op'] === 'create') {
        app(Billing::class)->create($workshop, $actor, $input['order'], $input['data']);
    } else {
        app(Billing::class)->mutate($workshop, $actor, $input['invoice'], $input['data'], $input['op'], $input['child'] ?? null);
    }
    echo json_encode(['result' => 'ok']);
} catch (ValidationException) {
    echo json_encode(['result' => 422]);
} catch (HttpException $e) {
    echo json_encode(['result' => $e->getStatusCode()]);
}
