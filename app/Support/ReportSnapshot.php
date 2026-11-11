<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;

class ReportSnapshot
{
    public function run(Closure $read): mixed
    {
        // Reuse an enclosing transaction (notably RefreshDatabase fixtures). HTTP reports own their snapshot.
        if (DB::transactionLevel() > 0) {
            return $read();
        }

        return DB::transaction(function () use ($read) {
            DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');

            return $read();
        });
    }
}
