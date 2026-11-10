<?php

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
    throw new RuntimeException('Local testing database required.');
}
$input = json_decode($argv[3], true, 512, JSON_THROW_ON_ERROR);
try {
    $movement = app(Inventory::class)->move(Workshop::findOrFail($argv[1]), User::findOrFail($argv[2]), $input['part'], $input['data'], $input['type'],
        $input['order'] ?? null, $input['line'] ?? null, $input['source'] ?? null, $input['final'] ?? false);
    echo json_encode(['result' => 'ok', 'id' => $movement->id]);
} catch (ValidationException) {
    echo json_encode(['result' => 422]);
} catch (HttpException $e) {
    echo json_encode(['result' => $e->getStatusCode()]);
}
