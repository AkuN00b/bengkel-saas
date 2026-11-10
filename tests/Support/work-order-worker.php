<?php

// Independent PHP process for PostgreSQL concurrency tests. No credentials in argv/output.
use App\Actions\WorkOrders;
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
    throw new RuntimeException('Concurrency tests require a local database.');
}
try {
    $action = $app->make(WorkOrders::class);
    $workshop = Workshop::findOrFail($argv[1]);
    $actor = User::findOrFail($argv[2]);
    if ($argv[3] === 'create') {
        $order = $action->create($workshop, $actor, ['vehicle_id' => (int) $argv[4], 'intent' => 'draft']);
    } else {
        $order = $action->mutate($workshop, $actor, (int) $argv[4], ['version' => (int) $argv[5], 'action' => 'open'], 'transition');
    }
    echo json_encode(['result' => 'ok', 'number' => $order->number]);
} catch (ValidationException) {
    echo json_encode(['result' => 'validation']);
} catch (HttpException $exception) {
    echo json_encode(['result' => $exception->getStatusCode()]);
}
