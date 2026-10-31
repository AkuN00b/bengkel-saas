<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->bigInteger('price_monthly')->nullable();
            $table->integer('user_limit')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
        DB::statement('ALTER TABLE plans ADD CONSTRAINT plans_price_nonnegative CHECK (price_monthly >= 0)');
        DB::statement('ALTER TABLE plans ADD CONSTRAINT plans_limit_positive CHECK (user_limit > 0)');
        DB::statement('ALTER TABLE plans ADD CONSTRAINT plans_active_price_required CHECK (NOT is_active OR price_monthly IS NOT NULL)');

        // Paid pricing is not approved yet; only the free trial is available.
        foreach (['trial' => 'Trial', 'starter' => 'Starter', 'pro' => 'Pro'] as $code => $name) {
            DB::table('plans')->insert([
                'code' => $code, 'name' => $name,
                'price_monthly' => $code === 'trial' ? 0 : null,
                'is_active' => $code === 'trial',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
