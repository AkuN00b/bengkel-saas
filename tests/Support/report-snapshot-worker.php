<?php

use App\Models\Workshop;
use App\Queries\Reporting;
use App\Support\ReportSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
TestDatabaseGuard::ensureSafe($app);
if (! in_array(config('database.connections.pgsql.host'), ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new RuntimeException('Local testing database required.');
}
$workshop = Workshop::findOrFail($argv[1]);
$result = app(ReportSnapshot::class)->run(function () use ($workshop) {
    $f = ['cutoff' => CarbonImmutable::now('UTC')->addMinute(), 'timezone' => 'Asia/Jakarta', 'data' => []];
    $queries = app(Reporting::class);
    $read = fn () => $queries->receivableTotal($queries->receivables($workshop, $f))->remaining;
    $before = $read();
    $isolation = DB::selectOne('SHOW transaction_isolation')->transaction_isolation;
    $readOnly = DB::selectOne('SHOW transaction_read_only')->transaction_read_only;
    echo "READY\n";
    flush();
    if (trim((string) fgets(STDIN)) !== 'continue') {
        throw new RuntimeException('Missing synchronization signal.');
    }
    $after = $read();
    $writeBlocked = false;
    try {
        DB::transaction(fn () => DB::table('workshops')->where('id', $workshop->id)->update(['name' => 'Forbidden write']));
    } catch (QueryException $e) {
        $writeBlocked = (string) $e->getCode() === '25006';
    }

    return compact('before', 'after', 'isolation', 'readOnly', 'writeBlocked');
});
echo json_encode($result, JSON_THROW_ON_ERROR);
